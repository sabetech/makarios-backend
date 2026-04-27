<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Region;
use Illuminate\Support\Facades\Auth;
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
        } elseif (!$user->hasRole(['Super Admin', 'Bishop', 'Stream Leader'])) {
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

    public function destroy($id) {
        $region = Region::find($id);

        if (!$region) {
            return $this->sendError('Region not found', [], 404);
        }

        $region->delete();

        return $this->sendResponse(null, 'Region deleted successfully.');
    }
}
