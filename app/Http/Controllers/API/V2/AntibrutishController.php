<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController;
use App\Models\Antibrutish;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AntibrutishController extends BaseController
{
    public function store(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'date' => 'required|date',
            'region_id' => 'required|exists:regions,id',
            'hours_prayed' => 'required|integer|min:0',
        ]);

        $antibrutish = Antibrutish::create([
            'campaign_id' => $request->campaign_id,
            'leader_id' => Auth::user()->id,
            'date' => $request->date,
            'region_id' => $request->region_id,
            'hours_prayed' => $request->hours_prayed,
        ]);

        return $this->sendResponse($antibrutish, 'Antibrutish submission created successfully.');
    }

    public function leaderSummary(Request $request)
    {
        $request->validate([
            'shepherdorial_cycle_id' => 'required|exists:shepherdorial_cycles,id',
        ]);

        $summary = Antibrutish::select('leader_id', 'region_id')
            ->selectRaw('SUM(hours_prayed) as total_hours_prayed')
            ->where('shepherdorial_cycle_id', $request->shepherdorial_cycle_id)
            ->with([
                'leader' => function ($query) {
                    $query->select('id', 'name', 'email');
                },
                'region:id,name'
            ])
            ->groupBy('leader_id', 'region_id')
            ->get();

        return $this->sendResponse($summary, 'Leader summary retrieved successfully.');
    }

    public function totalHours(Request $request)
    {
        $request->validate([
            'shepherdorial_cycle_id' => 'required|exists:shepherdorial_cycles,id',
        ]);

        $total = Antibrutish::where('shepherdorial_cycle_id', $request->shepherdorial_cycle_id)
            ->sum('hours_prayed');

        return $this->sendResponse(['total_hours_prayed' => $total], 'Total hours prayed retrieved successfully.');
    }
}
