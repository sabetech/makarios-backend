<?php

namespace App\Http\Controllers\API\V2;

use App\Models\Bacenta;
use App\Models\Church;
use App\Models\Member;
use App\Models\Stream;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\BaseController as BaseController;

class StreamController extends BaseController
{
    //
    public function index(): JsonResponse{
        $user = Auth::user();
        $streams = Stream::with(['overseer', 'church']);

        if ($user->hasRole(['Super Admin', 'General Admin'])) {
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

    public function create(Request $request): JsonResponse {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'meeting_day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'meeting_time' => 'required|date_format:H:i',
            'overseer_id' => 'nullable|exists:users,id',
        ]);

        $user = Auth::user();

        // Single-church setup: attach the stream to the church the user
        // heads, falling back to the user's own church, then the first church.
        $church = Church::where('head_pastor_id', $user->id)->first();
        if (!$church && !empty($user->church_id)) {
            $church = Church::find($user->church_id);
        }
        if (!$church) {
            $church = Church::first();
        }
        if (!$church) {
            return $this->sendError('No church found to attach the stream to.', [], 422);
        }

        $overseer = null;
        if ($request->filled('overseer_id')) {
            $overseer = User::find($request->overseer_id);
            if ($overseer && !$overseer->hasRole('Stream Lead')) {
                $overseer->assignRole('Stream Lead');
            }
        }

        $stream = Stream::create([
            'name' => $request->name,
            'description' => $request->description,
            'meeting_day' => $request->meeting_day,
            'meeting_time' => $request->meeting_time,
            'church_id' => $church->id,
            'stream_overseer_id' => $overseer?->id,
            'is_active' => true,
        ]);

        return $this->sendResponse($stream->load(['overseer', 'church']), 'Stream created successfully.');
    }

    public function update($id): JsonResponse {
        $stream = Stream::find($id);

        if (!$stream) {
            return $this->sendError('Stream not found.', ['error'=>'Stream not found'], 404);
        }

        return $this->sendResponse($stream, 'Stream updated successfully.');
    }

}
