<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use App\Models\Church;
use App\Models\Stream;
use App\Models\Region;
use App\Models\Bacenta;
use App\Models\Zone;
use App\Models\Member;
use Illuminate\Support\Facades\Log;

class UserController extends BaseController
{
    //
    /*
        structure ->>
        [
            {
                name: Churches,
                count: 1
            },
            {
                name: Streams,
                count: 3,
            },
            {
                name: Councils,
                count: 9,
            },
            {
                name: Bacentas,
                count: 27
            }
        ]

    */
    public function getUserViaEmail(Request $request) {
        $user = User::where('email', strtolower($request->email))->first();
        if (!$user) {
            return $this->sendError('User not found.');
        }

        return $this->sendResponse($user, 'User retrieved successfully.');
    }

    public function getDashboardSummary(Request $request) {

        $user = $request->user();
        //Get Role
        $roles = $user->getRoleNames();
        $role = "unassigned";
        if ($roles->count() > 0) {
            $role = $roles[0];
        }

        Log::info("User: " . $user->name . " Role: " . $role);

        $dashboardValues = [];
        switch ($role) {
            case "Super Admin":

            case "Bishop":
                $dashboardValues[] = [
                    "name" => "Churches",
                    "count" => Church::count()
                ];

                $dashboardValues[] = [
                    "name" => "Streams",
                    "count" => Stream::count()
                ];

                $dashboardValues[] = [
                    "name" => "Regions",
                    "count" => Region::count()
                ];

                $dashboardValues[] = [
                    "name" => "Zones",
                    "count" => Zone::count()
                ];

                $dashboardValues[] = [
                    "name" => "Bacentas",
                    "count" => Bacenta::count()
                ];

                $dashboardValues[] = [
                    "name" => "Members",
                    "count" => Member::count()
                ];
                break;

            case "Region Lead":
                $dashboardValues[] = [
                    "name" => "Regions",
                    "count" => $user->region()->count()
                ];

                $dashboardValues[] = [
                    "name" => "Zones",
                    "count" => Zone::where('region_id', $user->region->id)->count()
                ];

                $dashboardValues[] = [
                    "name" => "Bacentas",
                    "count" => Bacenta::where('region_id', $user->region->id)->count()
                ];

                $dashboardValues[] = [
                    "name" => "Members",
                    "count" =>  $user->region->members()->count()
                ];

                break;

            case "Zone Lead":
                $dashboardValues[] = [
                    "name" => "Zones",
                    "count" => Zone::where('id', $user->zone->id)->count()
                ];

                $dashboardValues[] = [
                    "name" => "Bacentas",
                    "count" => $user->zone->bacentas()->count()
                ];

                $dashboardValues[] = [
                    "name" => "Members",
                    "count" => $user->zone->members()->count()
                ];
                break;
            case "Bacenta Leader":
                $dashboardValues[] = [
                    "name" => "Bacentas",
                    "count" => Bacenta::where('id', $user->bacenta->id)->count()
                ];
                $dashboardValues[] = [
                    "name" => "Members",
                    "count" => ($user->bacenta) ? $user->bacenta->members()->count() : 0
                ];
                break;

            default:

        }
        return $this->sendResponse($dashboardValues, 'User retrieved successfully.');
    }
}
