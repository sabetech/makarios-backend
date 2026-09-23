<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Bacenta;
use App\Models\Member;
use App\Models\MicroChurch;
use App\Models\Region;
use App\Models\Service;
use App\Models\Stream;
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
        $region = Region::with('leader', 'stream','bacentas', 'membersThrough')->find($id);

        if (!$region) {
            return $this->sendError('Region not found', [], 404);
        }

        return $this->sendResponse($region, 'Region retrieved successfully.');
    }

    public function getBacentas($id) {
        $region = Region::find($id);

        if (!$region) {
            return $this->sendError('Region not found', [], 404);
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
