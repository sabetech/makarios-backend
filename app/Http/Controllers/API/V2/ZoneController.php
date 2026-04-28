<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use App\Models\Zone;
use Illuminate\Http\Request;
use App\Models\User;


class ZoneController extends BaseController
{
    //
    public function index() {
        //if user is super admin or bishop, return all zones
        //if user is region lead, return zones in their region
        //if user is zone lead, return their zone

        $user = Auth::user();
        $roles = $user->roles->pluck('name')->toArray();

        if (in_array('Super Admin', $roles) || in_array('Bishop', $roles)) {
            $zones = Zone::with(['region', 'stream', 'bacentas', 'leader'])->get();
        } elseif (in_array('Region Lead', $roles)) {
            $zones = Zone::with(['region', 'stream', 'leader', 'bacentas'])->whereHas('region', function($q) use ($user) {
                $q->where('id', $user->region_id);
            })->get();
        } elseif (in_array('Zone Lead', $roles)) {
            $zones = Zone::with(['region', 'stream', 'leader', 'bacentas'])->whereHas('leader', function($q) use ($user) {
                $q->where('id', $user->id);
            })->get();
        } else {
            return $this->sendError('Unauthorized', [], 403);
        }

        return $this->sendResponse($zones, 'Zones retrieved successfully.');
        
    }

    public function create(Request $request) {
        // Validate request data here
        $request->validate([
            'name' => 'required|string|max:255',
            'region_id' => 'required|exists:regions,id',
        ]);

        if ($request->leader_id) {
            $request->validate([
                'leader_id' => 'exists:users,id',
            ]);
        }

        $user = User::find($request->leader_id);
        
        if ($user && !$user->hasRole('Zone Lead')) {
            $user->assignRole('Zone Lead');
        }

        $zone = Zone::create([
            'name' => $request->name,
            'leader_id' => $request->leader_id ?? null,
            'region_id' => $request->region_id,
        ]);

        return $this->sendResponse($zone, 'Zone created successfully.');
    }

    public function show($id) {
        $zone = Zone::with(['region', 'stream', 'leader'])->find($id);

        if (!$zone) {
            return $this->sendError('Zone not found.', [], 404);
        }

        return $this->sendResponse($zone, 'Zone retrieved successfully.');
    }

    public function update(Request $request, $id) {
        $zone = Zone::find($id);

        if (!$zone) {
            return $this->sendError('Zone not found.', [], 404);
        }

        // Validate request data here
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'leader_id' => 'sometimes|required|exists:users,id',
            'region_id' => 'sometimes|required|exists:regions,id',
            'stream_id' => 'sometimes|required|exists:streams,id',
        ]);

        $zone->update($request->only(['name', 'leader_id', 'region_id', 'stream_id']));

        return $this->sendResponse($zone, 'Zone updated successfully.');
    }

    public function destroy($id) {
        $zone = Zone::find($id);

        if (!$zone) {
            return $this->sendError('Zone not found.', [], 404);
        }

        $zone->delete();

        return $this->sendResponse([], 'Zone deleted successfully.');
    }

}
