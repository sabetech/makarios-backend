<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserController extends BaseController
{
    public function index() {
        $users = User::with(['roles', 'region', 'bacenta', 'overseenStreams'])
            ->get()
            ->map(function($user) {
                $userArray = $user->toArray();
                $role = $user->roles->first()->name ?? null;
                $userArray['role'] = $role;
                if ($role == 'Stream Lead') {
                    $userArray['leading'] = [
                        "name" => $user->overseenStreams->pluck('name')->join(', ') ?? null,
                        "type" => "Stream",
                    ];
                } elseif ($role == 'Region Lead') {
                    $userArray['leading'] = [
                        "name" => $user->region->name ?? null,
                        "type" => "Region",
                    ];
                } elseif ($role == 'Bacenta Leader') {
                    $userArray['leading'] = [
                        "name" => $user->bacenta->name ?? null,
                        "type" => "Bacenta",
                    ];
                } else {
                    $userArray['leading'] = null;
                }
                unset($userArray['roles'], $userArray['region'], $userArray['bacenta'], $userArray['overseen_streams']);
                return $userArray;
            });

        return $this->sendResponse($users, 'Leaders retrieved successfully.');
    }

    public function getRoles() {
        $roles = Role::all();

        return $this->sendResponse($roles, 'Roles retrieved successfully.');
    }
}
