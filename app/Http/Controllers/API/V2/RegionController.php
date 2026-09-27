<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Bacenta;
use App\Models\Member;
use App\Models\MicroChurch;
use App\Models\Region;
use App\Models\Service;
use App\Models\Stream;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class RegionController extends BaseController
{
    //
    public function index() {
        $user = Auth::user();

        $query = Region::with('leader')
            ->with('stream')
            ->select('regions.*')
            ->selectRaw('(SELECT COUNT(*) FROM bacentas WHERE bacentas.region_id = regions.id) as bacenta_count')
            ->selectRaw('(SELECT COUNT(*) FROM members WHERE members.bacenta_id IN (SELECT id FROM bacentas WHERE bacentas.region_id = regions.id)) as members_count');

        if ($user->hasRole('Region Lead')) {
            $query->where('leader_id', $user->id);
        } elseif (!$user->hasRole(['Super Admin', 'General Admin', 'Bishop', 'Stream Leader'])) {
            return $this->sendError('Unauthorized', [], 403);
        }

        $regions = $query->get();

        return $this->sendResponse($regions, 'Regions retrieved successfully.');
    }

    public function create(Request $request) {
        // Validate request data here
        $request->validate([
            'name' => 'required|string|max:255',
            'leader_id' => 'required|exists:users,id',
            'stream_id' => 'required|exists:streams,id',
        ]);

        $region = Region::create([
            'name' => $request->name,
            'leader_id' => $request->leader_id,
            'stream_id' => $request->stream_id,
        ]);

        //make leader a region lead if they aren't already
        $leader = $region->leader;
        
        if (!$leader->hasRole('Region Lead')) {
            $leader->assignRole('Region Lead');
        }

        return $this->sendResponse($region, 'Region created successfully.');
    }

    public function show($id) {
        [$region, $error] = $this->getAccessibleRegion($id);
        if ($error) {
            return $error;
        }

        return $this->sendResponse($region->load(['leader', 'stream', 'bacentas', 'membersThrough']), 'Region retrieved successfully.');
    }

    public function getBacentas($id) {
        [$region, $error] = $this->getAccessibleRegion($id);
        if ($error) {
            return $error;
        }

        $bacentas = $region->bacentas()->with('leader')->get();

        $bacentas->each(function ($bacenta) {
            $bacenta->makeHidden(['zone_id', 'region_id', 'location_id', 'created_at', 'updated_at', 'deleted_at']);
            if ($bacenta->leader) {
                $bacenta->leader->makeHidden(['provider', 'provider_id', 'email_verified_at']);
            }
        });

        return $this->sendResponse($bacentas, 'Bacentas retrieved successfully.');
    }

    public function update(Request $request, $id) {
        [$region, $error] = $this->getAccessibleRegion($id);
        if ($error) {
            return $error;
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'leader_id' => 'sometimes|required|exists:users,id',
            'stream_id' => 'sometimes|required|exists:streams,id',
        ]);

        $region->update($request->only(['name', 'leader_id', 'stream_id']));

        // Make the new leader a region lead if they aren't already.
        $leader = $region->fresh()->leader;
        if ($leader && !$leader->hasRole('Region Lead')) {
            $leader->assignRole('Region Lead');
        }

        return $this->sendResponse($region->fresh()->load(['leader', 'stream']), 'Region updated successfully.');
    }

    public function getMembers($id) {
        [$region, $error] = $this->getAccessibleRegion($id);
        if ($error) {
            return $error;
        }

        $bacentaIds = Bacenta::where('region_id', $region->id)->pluck('id')->toArray();

        $members = Member::with(['bacenta', 'zone', 'region'])
            ->where('region_id', $region->id)
            ->when(!empty($bacentaIds), fn($q) => $q->orWhereIn('bacenta_id', $bacentaIds))
            ->orderBy('name')
            ->get();

        return $this->sendResponse([
            'total' => $members->count(),
            'members' => $members,
        ], 'Members retrieved successfully.');
    }

    public function getServices($id) {
        [$region, $error] = $this->getAccessibleRegion($id);
        if ($error) {
            return $error;
        }

        $bacentaIds = Bacenta::where('region_id', $region->id)->pluck('id')->toArray();

        $services = Service::with(['bacenta', 'serviceType'])
            ->where('region_id', $region->id)
            ->when(!empty($bacentaIds), fn($q) => $q->orWhereIn('bacenta_id', $bacentaIds))
            ->orderByDesc('date')
            ->get();

        return $this->sendResponse($services, 'Services retrieved successfully.');
    }

    /**
     * Resolve a region the current user may access: admins see any
     * region, a Stream Lead sees regions in their stream(s), and a
     * Region Lead sees only their own region.
     *
     * @return array{0: Region|null, 1: \Illuminate\Http\JsonResponse|null}
     */
    private function getAccessibleRegion($id): array
    {
        $user = Auth::user();
        $region = Region::find($id);

        if (!$region) {
            return [null, $this->sendError('Region not found', [], 404)];
        }

        if ($user->hasRole(['Super Admin', 'General Admin', 'Bishop'])) {
            return [$region, null];
        }

        if ($user->hasRole('Stream Lead')) {
            $streamIds = $user->overseenStreams()->pluck('id')->toArray();
            if (!empty($streamIds) && in_array($region->stream_id, $streamIds)) {
                return [$region, null];
            }
            if ($user->stream && (int) $region->stream_id === (int) $user->stream->id) {
                return [$region, null];
            }
            return [null, $this->sendError('Unauthorized', ['error' => 'You do not have access to this region.'], 403)];
        }

        if ($user->hasRole('Region Lead')) {
            if ($user->region && (int) $user->region->id === (int) $region->id) {
                return [$region, null];
            }
            return [null, $this->sendError('Unauthorized', ['error' => 'You do not have access to this region.'], 403)];
        }

        return [null, $this->sendError('Unauthorized', [], 403)];
    }

    public function transfer(Request $request, $id) {
        $request->validate([
            'stream_id' => 'required|exists:streams,id',
        ]);

        $region = Region::find($id);
        if (!$region) {
            return $this->sendError('Region not found', [], 404);
        }

        $destinationId = (int) $request->stream_id;
        if ((int) $region->stream_id === $destinationId) {
            return $this->sendError('Region is already in this stream.', [], 422);
        }

        $destination = Stream::find($destinationId);

        $bacentaIds = Bacenta::where('region_id', $region->id)->pluck('id')->toArray();

        $counts = DB::transaction(function () use ($region, $destinationId, $bacentaIds) {
            $region->update(['stream_id' => $destinationId]);

            // Bacentas and zones follow automatically via region_id
            // (zones resolve their stream through the region).
            $membersUpdated = 0;
            if (!empty($bacentaIds)) {
                $membersUpdated = Member::whereIn('bacenta_id', $bacentaIds)
                    ->update(['stream_id' => $destinationId]);
            }
            $servicesUpdated = Service::where('region_id', $region->id)
                ->update(['stream_id' => $destinationId]);
            $microChurchesUpdated = MicroChurch::where('region_id', $region->id)
                ->update(['stream_id' => $destinationId]);

            return [
                'members_updated' => $membersUpdated,
                'services_updated' => $servicesUpdated,
                'microchurches_updated' => $microChurchesUpdated,
            ];
        });

        return $this->sendResponse([
            'region' => $region->fresh()->load(['leader', 'stream']),
            'from_stream_id' => (int) $region->getOriginal('stream_id'),
            'to_stream' => $destination,
            'bacentas_moved' => count($bacentaIds),
            ...$counts,
        ], 'Region transferred successfully.');
    }

    public function destroy($id) {
        $region = Region::find($id);

        if (!$region) {
            return $this->sendError('Region not found', [], 404);
        }

        $region->delete();

        return $this->sendResponse(null, 'Region deleted successfully.');
    }
}
