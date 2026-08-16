<?php

namespace App\Http\Controllers\API\V2;

use App\Models\Bacenta;
use App\Models\Member;
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

    public function getMembers($id): JsonResponse
    {
        $stream = Stream::find($id);

        if (!$stream) {
            return $this->sendError('Stream not found.', ['error' => 'Stream not found'], 404);
        }

        $members = Member::where('stream_id', $stream->id)
            ->with(['bacenta', 'zone', 'region'])
            ->get();

        return $this->sendResponse([
            'total'   => $members->count(),
            'members' => $members,
        ], 'Members retrieved successfully.');
    }

    public function getBacentas($id): JsonResponse
    {
        $stream = Stream::find($id);

        if (!$stream) {
            return $this->sendError('Stream not found.', ['error'=>'Stream not found'], 404);
        }

        $bacentas = Bacenta::whereHas('region', fn($q) => $q->where('stream_id', $stream->id))
            ->with('leader')
            ->get();

        $bacentas->each(function ($bacenta) {
            $bacenta->makeHidden(['zone_id', 'region_id', 'location_id', 'created_at', 'updated_at', 'deleted_at']);
            if ($bacenta->leader) {
                $bacenta->leader->makeHidden(['provider', 'provider_id', 'email_verified_at']);
            }
        });

        return $this->sendResponse($bacentas, 'Bacentas retrieved successfully.');
    }

    public function update($id): JsonResponse {
        $stream = Stream::find($id);

        if (!$stream) {
            return $this->sendError('Stream not found.', ['error'=>'Stream not found'], 404);
        }

        return $this->sendResponse($stream, 'Stream updated successfully.');
    }

}
