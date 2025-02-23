<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Zone;
use App\Models\Stream;
use Illuminate\Support\Facades\Log;


class ZoneController extends BaseController
{
    //Get all zones ...
    public function index(Request $request) {

        $user = $request->user();
        $roles = $user->getRoleNames();
        $role = "unassigned";
        if ($roles->count() > 0) {
            $role = $roles[0];
        }

        $zones = Zone::with(['region', 'stream', 'leader'])->select();

        Log::info("User: " . $user->name . " Role: " . $role . " Getting Zones");

        $streamId = $request->get('stream_id', null);
        $regionId = $request->get('region_id', null);

        if ($streamId) {
            Log::info("Stream ID: " . $streamId);
            $stream = Stream::find($streamId);
            if ($stream) {
                $zones = $stream->zones()->with(['region', 'stream', 'leader']); //Need to test this very well. can be buggy!
            }
        }

        if ($regionId) {
            $zones = $zones->where('region_id', $regionId);
        }

        switch ($role) {
            case "Super Admin":
                $zones = $zones->get();
                return $this->sendResponse($zones, 'Zones retrieved successfully.');
                break;

            case "Bishop":
                $zones = $zones->get();
                return $this->sendResponse($zones, 'Zones retrieved successfully.');
                break;

            case "Region Lead":
                $zones = $zones->where('region_id', $user->region->id)->get();
                return $this->sendResponse($zones, 'Zones retrieved successfully.');
                break;

            case "Zone Lead":
                $zones = $zones->where('id', $user->zone->id)->get();
                return $this->sendResponse($zones, 'Zones retrieved successfully.');
                break;

            default:
                return $this->sendError('You are not authorized to view this page.');
                break;
        }

        return $this->sendError('You are not authorized to view this page.');

    }

    public function show($id) {
        $zone = Zone::with(['region', 'stream', 'leader.roles', 'bacentas'])->find($id);

        if (is_null($zone)) {
            return $this->sendError('Zone not found.');
        }
        $zone->members = $zone->members();
        return $this->sendResponse($zone, 'Zone retrieved successfully.');
    }
}
