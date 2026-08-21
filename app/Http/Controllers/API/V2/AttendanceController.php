<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Member;
use App\Models\MemberAttendance;
use App\Models\AttendanceSeverityThreshold;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceController extends BaseController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_id' => 'required|exists:services,id',
            'attendances' => 'required|array',
            'attendances.*.member_id' => 'required|exists:members,id',
            'attendances.*.status' => 'required|in:present,absent',
        ]);

        $user = Auth::user();
        $service = Service::findOrFail($data['service_id']);

        if (!$this->canAccessService($user, $service)) {
            return $this->sendError('Unauthorized', ['error' => 'You do not have access to this service.'], 403);
        }

        DB::transaction(function () use ($data) {
            foreach ($data['attendances'] as $attendance) {
                MemberAttendance::updateOrCreate(
                    [
                        'member_id' => $attendance['member_id'],
                        'service_id' => $data['service_id'],
                    ],
                    ['status' => $attendance['status']]
                );
            }

            $this->recalculateStreaks($data['attendances']);
        });

        return $this->sendResponse(null, 'Attendance recorded successfully.');
    }

    public function memberHistory(Request $request, int $memberId): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $member = Member::findOrFail($memberId);
        $user = Auth::user();

        if (!$this->canAccessMember($user, $member)) {
            return $this->sendError('Unauthorized', ['error' => 'You do not have access to this member.'], 403);
        }

        $history = MemberAttendance::with('service')
            ->where('member_id', $memberId)
            ->orderBy('id', 'desc');

        if ($request->filled('from')) {
            $history->whereHas('service', function ($q) use ($request) {
                $q->where('date', '>=', $request->from);
            });
        }

        if ($request->filled('to')) {
            $history->whereHas('service', function ($q) use ($request) {
                $q->where('date', '<=', $request->to);
            });
        }

        $currentStreak = $this->getCurrentStreak($memberId);
        $severity = $this->getSeverityForStreak($currentStreak);

        return $this->sendResponse([
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'consecutive_absences' => $currentStreak,
                'severity' => $severity,
            ],
            'history' => $history->get(),
        ], 'Member attendance history retrieved successfully.');
    }

    public function membersWithSeverity(Request $request): JsonResponse
    {
        $user = Auth::user();
        $bacentaId = $request->get('bacenta_id');
        $regionId = $request->get('region_id');

        $members = Member::with(['bacenta', 'region', 'zone']);

        // Explicit filters narrow the result set but NEVER widen it:
        // role scope is always applied on top.
        if ($bacentaId) {
            $members->where('bacenta_id', $bacentaId);
        }

        if ($regionId) {
            $members->where('region_id', $regionId);
        }

        $members = $this->applyRoleScope($members, $user);

        $members = $members->get()->map(function ($member) {
            $streak = $this->getCurrentStreak($member->id);
            $severity = $this->getSeverityForStreak($streak);

            return [
                'id' => $member->id,
                'name' => $member->name,
                'bacenta' => $member->bacenta,
                'region' => $member->region,
                'zone' => $member->zone,
                'consecutive_absences' => $streak,
                'severity' => $severity,
            ];
        });

        $sortBy = $request->get('sort', 'severity');
        if ($sortBy === 'severity') {
            $members = $members->sortByDesc('consecutive_absences')->values();
        }

        return $this->sendResponse($members, 'Members with severity retrieved successfully.');
    }

    public function thresholds(): JsonResponse
    {
        $thresholds = AttendanceSeverityThreshold::orderBy('min_absences')->get();
        return $this->sendResponse($thresholds, 'Severity thresholds retrieved successfully.');
    }

    public function serviceAttendance(int $serviceId): JsonResponse
    {
        $service = Service::findOrFail($serviceId);
        $user = Auth::user();

        if (!$this->canAccessService($user, $service)) {
            return $this->sendError('Unauthorized', ['error' => 'You do not have access to this service.'], 403);
        }

        $attendance = MemberAttendance::where('service_id', $serviceId)
            ->get(['member_id', 'status']);

        return $this->sendResponse($attendance, 'Service attendance retrieved successfully.');
    }

    private function recalculateStreaks(array $attendances): void
    {
        $memberIds = collect($attendances)->pluck('member_id')->unique();

        foreach ($memberIds as $memberId) {
            $counter = 0;

            MemberAttendance::where('member_id', $memberId)
                ->orderBy('id', 'desc')
                ->each(function ($record) use (&$counter) {
                    if ($record->status === 'absent') {
                        $counter++;
                        $record->update(['consecutive_absences' => $counter]);
                    } else {
                        $counter = 0;
                        $record->update(['consecutive_absences' => 0]);
                    }
                });
        }
    }

    private function getCurrentStreak(int $memberId): int
    {
        $latestPresent = MemberAttendance::where('member_id', $memberId)
            ->where('status', 'present')
            ->latest('id')
            ->first();

        $query = MemberAttendance::where('member_id', $memberId);

        if ($latestPresent) {
            $query->where('id', '>', $latestPresent->id);
        }

        return $query->where('status', 'absent')->count();
    }

    private function getSeverityForStreak(int $streak): ?array
    {
        $threshold = AttendanceSeverityThreshold::forStreak($streak)->first();

        if (!$threshold) {
            $threshold = AttendanceSeverityThreshold::orderByDesc('min_absences')->first();
        }

        return $threshold ? [
            'label' => $threshold->label,
            'color' => $threshold->color,
        ] : null;
    }

    /**
     * Apply the signed-in user's visibility scope to a Member or Service query.
     * Both tables share stream_id / region_id / zone_id / bacenta_id columns.
     */
    private function applyRoleScope($query, $user)
    {
        if ($user->hasRole(['Super Admin', 'Bishop'])) {
            return $query;
        }

        if ($user->hasRole('Stream Lead') && $user->stream) {
            return $query->where('stream_id', $user->stream->id);
        }

        if ($user->hasRole('Region Lead') && $user->region) {
            return $query->where('region_id', $user->region->id);
        }

        if ($user->hasRole('Zone Lead') && $user->zone) {
            return $query->where('zone_id', $user->zone->id);
        }

        if ($user->hasRole('Bacenta Leader') && $user->bacenta) {
            return $query->where('bacenta_id', $user->bacenta->id);
        }

        return $query->whereRaw('1 = 0');
    }

    private function canAccessMember($user, $member): bool
    {
        if ($user->hasRole(['Super Admin', 'Bishop'])) {
            return true;
        }

        if ($user->hasRole('Stream Lead')) {
            return $user->stream && $member->stream_id === $user->stream->id;
        }

        if ($user->hasRole('Region Lead')) {
            return $user->region && $member->region_id === $user->region->id;
        }

        if ($user->hasRole('Zone Lead')) {
            return $user->zone && $member->zone_id === $user->zone->id;
        }

        if ($user->hasRole('Bacenta Leader')) {
            return $user->bacenta && $member->bacenta_id === $user->bacenta->id;
        }

        return false;
    }

    private function canAccessService($user, $service): bool
    {
        if ($user->hasRole(['Super Admin', 'Bishop'])) {
            return true;
        }

        if ($user->hasRole('Stream Lead')) {
            return $user->stream && $service->stream_id === $user->stream->id;
        }

        if ($user->hasRole('Region Lead')) {
            return $user->region && $service->region_id === $user->region->id;
        }

        if ($user->hasRole('Zone Lead')) {
            return $user->zone && $service->zone_id === $user->zone->id;
        }

        if ($user->hasRole('Bacenta Leader')) {
            return $user->bacenta && $service->bacenta_id === $user->bacenta->id;
        }

        return false;
    }
}
