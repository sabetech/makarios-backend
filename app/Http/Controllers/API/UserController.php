<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;

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
        $role = $user->getRoleNames()[0];
        $dashboardValues = [];
        switch ($role) {
            case "Super Admin":

            case "Bishop":
                $dashboardValues[] = [
                    "name" => "Churches",
                    "count" => $user->churches->count()
                ];

                $dashboardValues[] = [
                    "name" => "Streams",
                    "count" => $user->streams->count()
                ];


            case "Overseer":
                $dashboardValues[] = [
                    "name" => "Council",

                ];

            case "Bacenta Leader":

                break;
            case "Fellowship Leader":
                break;
            default:

        }
        return $this->sendResponse($dashboardValues, 'User retrieved successfully.');
    }
}
