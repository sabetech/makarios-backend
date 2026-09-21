<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Arrival;
use App\Models\Church;
use App\Models\Service;
use App\Models\Stream;
use App\Models\Region;
use App\Models\Bacenta;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends BaseController
{
    public function index() {
        $user = auth()->user();
        $counts = [];

        if ($user->hasRole(['Super Admin', 'Bishop'])) {
            $counts['churches'] = Church::count();
            $counts['streams'] = Stream::count();
            $counts['regions'] = Region::count();
            $counts['bacentas'] = Bacenta::count();
            $counts['members'] = Member::count();
            $counts['leaders'] = User::whereHas('roles', function($q) {
                $q->whereIn('name', ['Super Admin', 'Bishop', 'Stream Lead', 'Region Lead', 'Zone Lead', 'Bacenta Leader']);
            })->count();
        } elseif ($user->hasRole('Stream Lead')) {
            $counts['streams'] = Stream::count();
            $counts['regions'] = Region::count();
            $counts['bacentas'] = Bacenta::count();
            $counts['members'] = Member::count();
            $counts['leaders'] = User::whereHas('roles', function($q) {
                $q->whereIn('name', ['Stream Lead', 'Region Lead', 'Zone Lead', 'Bacenta Leader']);
            })->count();
        } elseif ($user->hasRole('Region Lead')) {
            $region = $user->region;
            if ($region) {
                $counts['regions'] = 1;
                $counts['bacentas'] = Bacenta::where('region_id', $region->id)->count();
                $counts['members'] = Member::where('region_id', $region->id)->count();
                $counts['leaders'] = User::whereHas('roles', function($q) {
                    $q->whereIn('name', ['Bacenta Leader']);
                })->where(function($q) use ($region, $user) {
                    $q->where('id', $user->id)
                      ->orWhereHas('bacenta', function($q2) use ($region) {
                          $q2->where('region_id', $region->id);
                      });
                })->count();
            }
        } elseif ($user->hasRole('Bacenta Leader')) {
            $bacenta = $user->bacenta;
            if ($bacenta) {
                $counts['bacentas'] = Bacenta::where('leader_id', $user->id)->count();
                $counts['members'] = Member::where('bacenta_id', $bacenta->id)->count();
                $counts['leaders'] = 1;
            }
        } else {
            return $this->sendError('Unauthorized', [], 403);
        }

        return $this->sendResponse($counts, 'Dashboard counts retrieved successfully.');
    }

    /**
     * Real (non-dummy) dashboard summary figures.
     *
     * - avg_attendance: average attendance over the user's last 8 services
     * - weekly_income: sum of offerings in the trailing 7 days
     * - bussing: total bussed on the latest date with arrivals in scope
     * - weekly_trend: per-week attendance + income buckets for the last 4 weeks
     */
    public function summary() {
        $user = auth()->user();

        $services = $this->scopedServices($user);
        if (!$services) {
            return $this->sendError('Unauthorized', [], 403);
        }

        $now = Carbon::now();

        $recentAttendances = (clone $services)
            ->orderByDesc('date')
            ->limit(8)
            ->pluck('attendance');
        $avgAttendance = $recentAttendances->count() > 0
            ? (int) round($recentAttendances->avg())
            : null;

        $weeklyIncome = (float) (clone $services)
            ->where('date', '>=', $now->copy()->subDays(7))
            ->sum('offering');

        $trend = [];
        for ($i = 3; $i >= 0; $i--) {
            $end = $now->copy()->subDays($i * 7);
            $start = $now->copy()->subDays(($i + 1) * 7);
            $bucket = (clone $services)->whereBetween('date', [$start, $end]);
            $trend[] = [
                'name' => $start->format('M j'),
                'attendance' => (int) $bucket->sum('attendance'),
                'income' => (float) $bucket->sum('offering'),
            ];
        }

        $bacentaIds = $this->scopedBacentaIds($user);
        $bussing = null;
        $bussingDate = null;
        if ($bacentaIds !== null && count($bacentaIds) > 0) {
            $bussingDate = Arrival::whereIn('bacenta_id', $bacentaIds)->max('date');
            if ($bussingDate) {
                $bussing = (int) Arrival::whereIn('bacenta_id', $bacentaIds)
                    ->where('date', $bussingDate)
                    ->sum('number_bussed');
            }
        }

        return $this->sendResponse([
            'avg_attendance' => $avgAttendance,
            'weekly_income' => $weeklyIncome,
            'bussing' => $bussing,
            'bussing_date' => $bussingDate,
            'weekly_trend' => $trend,
        ], 'Dashboard summary retrieved successfully.');
    }

    /**
     * Services query scoped to what the user may see.
     * Mirrors ServiceController@index scoping. Returns null when unauthorized.
     */
    private function scopedServices($user) {
        $query = Service::query();

        if ($user->hasRole(['Super Admin', 'Bishop'])) {
            return $query;
        }
        if ($user->hasRole('Stream Lead')) {
            return $query->whereHas('stream', function ($q) use ($user) {
                $q->whereHas('overseer', function ($q2) use ($user) {
                    $q2->where('users.id', $user->id);
                });
            });
        }
        if ($user->hasRole('Region Lead')) {
            return $query->whereHas('region', function ($q) use ($user) {
                $q->whereHas('leader', function ($q2) use ($user) {
                    $q2->where('users.id', $user->id);
                });
            });
        }
        if ($user->hasRole('Bacenta Leader')) {
            return $query->whereHas('bacenta', function ($q) use ($user) {
                $q->whereHas('leader', function ($q2) use ($user) {
                    $q2->where('users.id', $user->id);
                });
            });
        }

        return null;
    }

    /**
     * Bacenta ids whose arrivals the user may see.
     * Mirrors ArrivalController@index scoping. Returns null when unauthorized.
     */
    private function scopedBacentaIds($user): ?array {
        if ($user->hasRole(['Super Admin', 'Bishop'])) {
            return Bacenta::pluck('id')->toArray();
        }
        if ($user->hasRole('Stream Lead')) {
            $streamIds = $user->overseenStreams()->pluck('id')->toArray();
            if (empty($streamIds)) {
                return Bacenta::pluck('id')->toArray();
            }
            return Bacenta::whereHas('region', function ($q) use ($streamIds) {
                $q->whereIn('stream_id', $streamIds);
            })->pluck('id')->toArray();
        }
        if ($user->hasRole('Region Lead')) {
            $region = $user->region;
            if (!$region) {
                return [];
            }
            return Bacenta::where('region_id', $region->id)->pluck('id')->toArray();
        }
        if ($user->hasRole('Bacenta Leader')) {
            return Bacenta::where('leader_id', $user->id)->pluck('id')->toArray();
        }

        return null;
    }
}
