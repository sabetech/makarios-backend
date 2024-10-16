<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\Basonta;

class BasontaController extends BaseController
{
    //
    public function index()
    {
        $basontas = Basonta::all();
        return $this->sendResponse($basontas, 'Basontas retrieved successfully.');
    }
}
