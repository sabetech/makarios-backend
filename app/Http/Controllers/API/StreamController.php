<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Stream;

class StreamController extends BaseController
{
    //
    public function index() {
        $streams = Stream::with(['overseer', 'church'])->get();

        return $this->sendResponse($streams, 'Streams retrieved successfully.');
    }

    public function show($id) {
        $stream = Stream::find($id);

        $stream->regionalInfo = $stream->regions()->with(['zones', 'bacentas'])->get();

        //TODO:: handle stream not found later
        return $this->sendResponse($stream, 'Stream retrieved successfully.');
    }
}
