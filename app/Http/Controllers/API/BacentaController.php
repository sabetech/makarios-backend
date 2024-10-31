<?php

namespace App\Http\Controllers\API;

use App\Models\Bacenta;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;

class BacentaController extends BaseController
{
    //
    public function index() {
        $bacentas = Bacenta::select();

        $user = Auth::user();
        if ($user) {
            if (($user->roles->count() > 0) && ($user->roles[0]->name) == 'Super Admin' ) {
                $bacentas = $bacentas->get();
            }

            if (($user->roles->count() > 0) && ($user->roles[0]->name) == 'Bishop' ) {
                $bacentas = $bacentas->get();
            }

            if (($user->roles->count() > 0) && ($user->roles[0]->name) == 'Region Lead') {
                $bacentas = $bacentas->where('region_id', $user->region->id)->get();
            }

            if (($user->roles->count() > 0) && ($user->roles[0]->name) == 'Zone Lead') {
                $bacentas = $bacentas->where('zone_id', $user->zone->id)->get();
            }
        }

        return $this->sendResponse($bacentas, 'Bacentas retrieved successfully.');
    }
}
