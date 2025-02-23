<?php

namespace App\Http\Controllers\API;;

use Illuminate\Http\Request;
use App\Models\Region;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\API\BaseController as BaseController;

class RegionController extends BaseController
{
    //
    public function index(Request $request) {
        $user = Auth::user();
        $regions = Region::with(['leader', 'stream'])->select();

        $streamId = $request->get('stream_id', null);

        if ($streamId) {
            $regions = $regions->where('stream_id', $streamId);
        }

        if (!$user) {
            return $this->sendError('Unauthorised.', ['error'=>'User not found'], 401);
        }

        if (($user->roles->count() > 0) && ($user->roles[0]->name) == 'Super Admin' ) {
            $regions =$regions->get();
        }

        if (($user->roles->count() > 0) && ($user->roles[0]->name) == 'Bishop' ) {
            $regions =$regions->get(); //Refactor this to take into account the church
        }

        if (($user->roles->count() > 0) && ($user->roles[0]->name) == 'Region Lead') {
            $regions = $regions->where('id', $user->region->id)->get();
        }

        return $this->sendResponse($regions, 'Regions retrieved successfully.');
    }

    public function show($id) {
        $region = Region::with(['leader.roles', 'stream', 'zones', 'bacentas.leader'])->find($id);

        if (is_null($region)) {
            return $this->sendError('Region not found.');
        }

        $region->members = $region->members();

        return $this->sendResponse($region, 'Region retrieved successfully.');
    }

}
