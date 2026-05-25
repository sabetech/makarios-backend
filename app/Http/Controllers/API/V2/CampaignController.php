<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController;
use App\Models\Campaign;

class CampaignController extends BaseController
{
    public function index()
    {
        $campaigns = Campaign::with('shepherdorialCycle:id,name,cycle_start,cycle_end')->get();

        return $this->sendResponse($campaigns, 'Campaigns retrieved successfully.');
    }
}
