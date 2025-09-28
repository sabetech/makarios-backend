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
use App\Models\MicroChurch;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

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
                $dashboardValues[] = [
                    "name" => "Microchurches",
                    "count" => MicroChurch::count() // Placeholder for Micro Churches count
                ];

                $dashboardValues[] = [
                    "name" => "Leaders",
                    "count" => User::count()
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

                $dashboardValues[] = [
                    "name" => "Microchurches",
                    "count" => $user->region->microchurches()->count()
                ];

                break;

            case "Microchurch Leader":
                $dashboardValues[] = [
                    "name" => "Microchurches",
                    "count" => $user->Microchurches()->count()
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

    public function index(Request $request) {
        $users = User::with('roles')->get();

        //Filter by role if provided
        $role = $request->get('role', null);
        if ($role) {
            $users = $users->filter(function($user) use ($role) {
                return $user->hasRole($role);
            });
        }

        return $this->sendResponse($users, 'Leaders retrieved successfully.');
    }

    public function getRoles() {
        $roles = Role::all();

        Log::info("Roles: ", [$roles]);

        return $this->sendResponse($roles, 'Roles retrieved successfully.');
    }

    public function createRole(Request $request) {
        $roleName = $request->get('name');
        if (!$roleName) {
            return $this->sendError('Role name is required.');
        }

        $role = Role::create(['name' => $roleName]);

        Log::info("Role created: ", [$role]);

        return $this->sendResponse($role, 'Role created successfully.');
    }

    public function deleteRole(Request $request, $id) {
        $role = Role::find($id);
        if (!$role) {
            return $this->sendError('Role not found.');
        }

        $role->delete();

        Log::info("Role deleted: ", [$role]);

        return $this->sendResponse([], 'Role deleted successfully.');
    }


    public function create(Request $request) {
        $user = new User();
        $user->name = $request->get('name');
        $user->email = strtolower($request->get('email'));

        //$user->save();

        //Assign role if provided

        $roleIds = $request->roles;
        Log::info([ "Role Name: "=>$roleIds ]);
        if ($roleIds) {
            $roles = Role::whereIn('id', $roleIds)->pluck('name')->toArray();
            if ($roles) {
                Log::info(["Roles to be assigned: " => $roles]);
                $user->syncRoles($roles);
            }
        }

        Log::info("User created: ", [$user]);
        $user->roles()->sync($roleIds);
        return $this->sendResponse($user, 'User created successfully.');

    }

}
