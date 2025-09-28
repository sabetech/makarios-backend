<?php
namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\Microchurch;
use Illuminate\Support\Facades\Log;

class MicrochurchController extends BaseController
{
    //
    public function index()
    {
        // Logic to return a list of microchurches
        $microchurches = Microchurch::with('stream', 'region', 'leader')->get();
        return response()->json(['data' => $microchurches]);
    }

    public function show(Microchurch $microchurch)
    {
        $microchurch->load('stream', 'region', 'leader', 'services', 'members');
        // Logic to return a specific microchurch by ID
        return response()->json(['data' => $microchurch]);
        // This is a placeholder; replace with actual logic to fetch microchurch details
    }

    public function create(Request $request)
    {
        Log::info('Creating microchurch with data: ', $request->all());

        $name = $request->input('name');
        $streamAndRegionIds = json_decode($request->input('stream_and_region'), true);
        $stream = $streamAndRegionIds[0];
        $region = $streamAndRegionIds[1];
        $leader = json_decode($request->input('leader'));

        Log::info('Parsed leader ID: ', ['leader' => $leader]);

        $data = [
            'name' => $name,
            'stream_id' => $stream,
            'region_id' => $region,
            'leader_id' => $leader->id ?? null,
            'location' => $request->input('location'),
        ];

        Log::info("Microchurch data prepared for creation: ", $data);

        // Save the microchurch to the database
        $microchurch = Microchurch::create([
            'name' => $data['name'],
            'stream_id' => $data['stream_id'],
            'region_id' => $data['region_id'],
            'leader_id' => $data['leader_id'],
            'location' => $data['location'],
        ]);

        return response()->json(['microchurch' => $microchurch], 201);
    }
}
