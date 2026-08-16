<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Church;
use App\Models\Stream;
use App\Models\Region;
use App\Models\Bacenta;
use App\Models\Member;
use App\Models\User;

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
}
