<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Region;
use Auth;

use App\Http\Controllers\API\BaseController as BaseController;

class RegionController extends BaseController
{
    //
    public function index(Request $request) {
        $user = Auth::user();
        $regions = Region::select();
        if ($user) {
            if (($user->roles->count() > 0) && ($user->roles[0]->name) == 'Super Admin' ) {
                $regions =$regions->get();
            }

            if (($user->roles->count() > 0) && ($user->roles[0]->name) == 'Bishop' ) {
                $regions = $regions->where('church_id', $user->church->id)->get();
            }

            if (($user->roles->count() > 0) && ($user->roles[0]->name) == 'Region Lead') {
                $regions = $regions->where('id', $user->region->id)->get();
            }




        }

        return $this->sendResponse($regions, 'Regions retrieved successfully.');
    }
}
