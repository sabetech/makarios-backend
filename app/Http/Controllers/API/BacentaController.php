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
            if (($user->roles->count() > 0) && ($user->roles[0]->name) == 'Overseer' ) {
                $bacentas = $bacentas->where('council_id', $user->council->id);
            }

            if (($user->roles->count() > 0) && ($user->roles[0]->name) == 'Bacenta Leader') {
                $bacentas = $bacentas->where('leader_id', $user->id);
            }
        }

        return $this->sendResponse($bacentas->get(), 'Bacentas retrieved successfully.');
    }
}
