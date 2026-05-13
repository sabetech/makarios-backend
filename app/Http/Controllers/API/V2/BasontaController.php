<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Models\Basonta;
use Illuminate\Http\Request;

class BasontaController extends Controller
{
    //
    public function index() {

        $basontas = Basonta::all();

        return response()->json([
            'success' => true,
            'data' => $basontas
        ]);
    }
}
