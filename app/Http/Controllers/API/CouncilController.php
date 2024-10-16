<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;

class CouncilController extends BaseController
{
    //
    public function index()
    {
        $councils = Council::all();
        return $this->sendResponse($councils, 'Councils retrieved successfully.');
    }

}
