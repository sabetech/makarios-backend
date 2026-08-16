<?php

namespace App\Http\Controllers\API\V2;

use App\Models\Stream;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\BaseController as BaseController;

class StreamController extends BaseController
{
    //
    public function index(): JsonResponse{
        $user = Auth::user();
        $streams = Stream::with(['overseer', 'church']);

        if ($user->hasRole('Super Admin')) {
            return $this->sendResponse($streams->get(), 'Streams retrieved successfully.');
        }

        if ($user->hasRole('Bishop')) {
            $streams->whereHas('church', fn($q) => $q->where('head_pastor_id', $user->id));
            return $this->sendResponse($streams->get(), 'Streams retrieved successfully.');
        }

        return $this->sendResponse([], 'Streams retrieved successfully.');
    }

    public function show($id): JsonResponse {
        $stream = Stream::with(['overseer', 'church'])->find($id);

        if (!$stream) {
            return $this->sendError('Stream not found.', ['error'=>'Stream not found'], 404);
        }

        $stream->regionalInfo = $stream->regions()->with(['bacentas'])->get();

        return $this->sendResponse($stream, 'Stream retrieved successfully.');
    }

    public function getRegions($id): JsonResponse {
        $stream = Stream::find($id);

        if (!$stream) {
            return $this->sendError('Stream not found.', ['error'=>'Stream not found'], 404);
        }

        $regions = $stream->regions()->with('leader')->get();

        return $this->sendResponse($regions, 'Regions retrieved successfully.');
    }

    public function update($id): JsonResponse {
        $stream = Stream::find($id);

        if (!$stream) {
            return $this->sendError('Stream not found.', ['error'=>'Stream not found'], 404);
        }

        return $this->sendResponse($stream, 'Stream updated successfully.');
    }

}
