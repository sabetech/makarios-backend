<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController;
use App\Models\SheepSheeking;
use App\Models\ShepherdorialCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SheepSheekingController extends BaseController
{
    public function index()
    {
        $records = SheepSheeking::with([
            'leader:id,name',
            'leader.region:id,name',
            'memberVisited:id,name,region_id,img_url',
            'memberVisited.region:id,name',
        ])->orderBy('date', 'desc')->get();

        return $this->sendResponse($records, 'Sheep sheeking records retrieved successfully.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'member_id' => 'required|exists:members,id',
            'report' => 'nullable|string',
            'campaign_id' => 'required|exists:campaigns,id',
        ]);

        $cycle = ShepherdorialCycle::whereDate('cycle_start', '<=', $request->date)
            ->whereDate('cycle_end', '>=', $request->date)
            ->first();

        if (!$cycle) {
            return $this->sendError('No active cycle found for the given date.', [], 404);
        }

        $sheepSheeking = SheepSheeking::create([
            'date' => $request->date,
            'leader_id' => Auth::user()->id,
            'member_visited_id' => $request->member_id,
            'visitation_report' => $request->report,
            'campaign_id' => $request->campaign_id,
            'shepherdorial_cycle_id' => $cycle->id,
        ]);

        return $this->sendResponse($sheepSheeking, 'Sheep sheeking recorded successfully.');
    }

    public function totalVisits(Request $request)
    {
        $request->validate([
            'shepherdorial_cycle_id' => 'required|exists:shepherdorial_cycles,id',
        ]);

        $total = SheepSheeking::where('shepherdorial_cycle_id', $request->shepherdorial_cycle_id)->count();

        return $this->sendResponse(['total_visits' => $total], 'Total visits retrieved successfully.');
    }
}
