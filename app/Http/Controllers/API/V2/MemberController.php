<?php

namespace App\Http\Controllers\API\V2;

use Illuminate\Support\Facades\Auth;
use App\Models\Member;
use App\Http\Controllers\API\BaseController as BaseController;

class MemberController extends BaseController
{
    //
    public function index()
    {
        //Based on who is logged in, return the members that belong to the church/region/bacenta that the user belongs to.
        //Get Logged in user
        $user = Auth::user();

        if (!$user) {
            return $this->sendError('Unauthorised.', ['error'=>'User not found'], 401);
        }



        $members = Member::select();



        return $this->sendResponse($members->get(), 'Members retrieved successfully.');
    }
}
