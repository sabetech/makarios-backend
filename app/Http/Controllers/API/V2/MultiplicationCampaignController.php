<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController;
use App\Models\MultiplicationCampaign;
use App\Models\ShepherdorialCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MultiplicationCampaignController extends BaseController
{
    public function index()
    {
        $records = MultiplicationCampaign::with([
            'region:id,name',
            'campaign:id,name',
            'leader:id,name',
            'shepherdorialCycle:id,name',
        ])->orderBy('date', 'desc')->get();

        return $this->sendResponse($records, 'Multiplication campaigns retrieved successfully.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'souls_saved' => 'required|integer|min:0',
            'region_id' => 'required|exists:regions,id',
            'campaign_id' => 'required|exists:campaigns,id',
            'outreach_activity' => 'required|string',
        ]);

        $cycle = ShepherdorialCycle::whereDate('cycle_start', '<=', $request->date)
            ->whereDate('cycle_end', '>=', $request->date)
            ->first();

        if (!$cycle) {
            return $this->sendError('No active cycle found for the given date.', [], 404);
        }

        $multiplication = MultiplicationCampaign::create([
            'date' => $request->date,
            'souls_saved' => $request->souls_saved,
            'region_id' => $request->region_id,
            'campaign_id' => $request->campaign_id,
            'leader_id' => Auth::user()->id,
            'shepherdorial_cycle_id' => $cycle->id,
            'outreach_activity' => $request->outreach_activity,
        ]);

        return $this->sendResponse($multiplication, 'Multiplication campaign recorded successfully.');
    }

    public function totalSouls(Request $request)
    {
        $request->validate([
            'shepherdorial_cycle_id' => 'required|exists:shepherdorial_cycles,id',
        ]);

        $total = MultiplicationCampaign::where('shepherdorial_cycle_id', $request->shepherdorial_cycle_id)
            ->sum('souls_saved');

        return $this->sendResponse(['total_souls_saved' => $total], 'Total souls saved retrieved successfully.');
    }
}
