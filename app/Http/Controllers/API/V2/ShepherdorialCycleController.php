<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController;
use App\Models\ShepherdorialCycle;

class ShepherdorialCycleController extends BaseController
{
    public function index()
    {
        $cycles = ShepherdorialCycle::orderBy('cycle_start', 'desc')->get();
        return $this->sendResponse($cycles, 'Shepherdorial cycles retrieved successfully.');
    }

    public function show($id)
    {
        $cycle = ShepherdorialCycle::findOrFail($id);
        return $this->sendResponse($cycle, 'Shepherdorial cycle retrieved successfully.');
    }

    public function create()
    {
        $data = request()->validate([
            'cycle_start' => 'required|date',
            'cycle_end' => 'required|date|after:cycle_start',
        ]);

        $cycle = ShepherdorialCycle::create($data);
        return $this->sendResponse($cycle, 'Shepherdorial cycle created successfully.', 201);
    }
}
