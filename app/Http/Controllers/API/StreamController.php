<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Stream;
use Illuminate\Support\Facades\Auth;

class StreamController extends BaseController
{
    //
    public function index() {
        $user = Auth::user();
        $streams = Stream::with(['overseer', 'church'])->select();

        if (!$user) {
            return $this->sendError('Unauthorised.', ['error'=>'User not found'], 401);
        }

        if (($user->roles->count() > 0) && ($user->roles[0]->name) == 'Super Admin' ) {
            $streams = $streams->get();
            return $this->sendResponse($streams, 'Streams retrieved successfully.');
        }

        if (($user->roles->count() > 0) && ($user->roles[0]->name) == 'Bishop' ) {
            $streams = $streams->get(); //Refactor this to take into account the church
            return $this->sendResponse($streams, 'Streams retrieved successfully.');
        }

        return $this->sendResponse([], 'Streams retrieved successfully.');
    }

    public function show($id) {
        $stream = Stream::find($id);

        $stream->regionalInfo = $stream->regions()->with(['zones', 'bacentas'])->get();

        //TODO:: handle stream not found later
        return $this->sendResponse($stream, 'Stream retrieved successfully.');
    }
}
