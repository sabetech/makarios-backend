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
use Illuminate\Support\Collection;

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

            collect($data['attendances'])->pluck('member_id')
                ->unique()
                ->each(fn ($memberId) => $this->recalculateMemberStreaks($memberId));
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

        $history = MemberAttendance::query()
            ->join('services', 'services.id', '=', 'member_attendance.service_id')
            ->where('member_attendance.member_id', $memberId)
            ->select('member_attendance.*')
            ->with('service')
            ->orderByDesc('services.date');

        if ($request->filled('from')) {
            $history->where('services.date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $history->where('services.date', '<=', $request->to);
        }

        $currentStreak = (int) $member->current_consecutive_absences;

        return $this->sendResponse([
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'consecutive_absences' => $currentStreak,
                'severity' => $this->resolveSeverity(
                    AttendanceSeverityThreshold::forStreak($currentStreak)->first()
                ),
            ],
            'history' => $history->get(),
        ], 'Member attendance history retrieved successfully.');
    }

    public function membersWithSeverity(Request $request): JsonResponse
    {
        $user = Auth::user();
        $bacentaId = $request->get('bacenta_id');
        $regionId = $request->get('region_id');
        $sortDescByStreak = $request->get('sort', 'severity') === 'severity';

        // Explicit filters narrow the result set but NEVER widen it:
        // role scope is always applied on top.
        $members = Member::with(['bacenta', 'region', 'zone']);

        if ($bacentaId) {
            $members->where('bacenta_id', $bacentaId);
        }

        if ($regionId) {
            $members->where('region_id', $regionId);
        }

        $members = $this->applyRoleScope($members, $user);

        if ($sortDescByStreak) {
            $members->orderByDesc('members.current_consecutive_absences');
        }

        // Single query + in-memory threshold resolution: no per-member queries.
        $thresholds = AttendanceSeverityThreshold::orderBy('min_absences')->get();

        $result = $members->get()->map(function ($member) use ($thresholds) {
            $streak = (int) $member->current_consecutive_absences;

            return [
                'id' => $member->id,
                'name' => $member->name,
                'bacenta' => $member->bacenta,
                'region' => $member->region,
                'zone' => $member->zone,
                'consecutive_absences' => $streak,
                'severity' => $this->resolveSeverityFromCollection($thresholds, $streak),
            ];
        })->values();

        return $this->sendResponse($result, 'Members with severity retrieved successfully.');
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

    /**
     * Recalculate the stored running counts for every attendance record of a
     * member, plus the cached current streak on the member record.
     *
     * Records are processed in CHRONOLOGICAL order by service date (not id),
     * so backdated services are handled correctly. Each record's
     * consecutive_absences value is the streak length ending at that service.
     */
    private function recalculateMemberStreaks(int $memberId): void
    {
        $records = $this->memberRecordsByServiceDate($memberId)->get();

        $counter = 0;
        foreach ($records as $record) {
            if ($record->status === 'absent') {
                $counter++;
            } else {
                $counter = 0;
            }

            if ((int) $record->consecutive_absences !== $counter) {
                $record->consecutive_absences = $counter;
                $record->save();
            }
        }

        Member::where('id', $memberId)
            ->update(['current_consecutive_absences' => $counter]);
    }

    private function memberRecordsByServiceDate(int $memberId)
    {
        return MemberAttendance::query()
            ->join('services', 'services.id', '=', 'member_attendance.service_id')
            ->where('member_attendance.member_id', $memberId)
            ->select('member_attendance.*')
            ->orderBy('services.date')
            ->orderBy('member_attendance.id');
    }

    private function resolveSeverity(?AttendanceSeverityThreshold $threshold): ?array
    {
        return $threshold ? [
            'label' => $threshold->label,
            'color' => $threshold->color,
        ] : null;
    }

    private function resolveSeverityFromCollection(Collection $thresholds, int $streak): ?array
    {
        $threshold = $thresholds->first(
            fn ($t) => $t->min_absences <= $streak
                && (is_null($t->max_absences) || $t->max_absences >= $streak)
        );

        // Fallback: streak beyond all ranges maps to the highest threshold
        if (!$threshold) {
            $threshold = $thresholds->sortByDesc('min_absences')->first();
        }

        return $this->resolveSeverity($threshold);
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
