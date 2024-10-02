<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \App\Models\Church;
use App\Http\Controllers\API\BaseController as BaseController;

class ChurchController extends BaseController
{
    //
    public function index()
    {
        $churches = Church::all();
        return $this->sendResponse($churches, 'Churches retrieved successfully.');
    }

    public function show($id)
    {
        $church = Church::find($id);
        return $this->sendResponse($church, 'Church retrieved successfully.');
    }

    public function store(Request $request)
    {
        $church = Church::create($request->all());
        return $this->sendResponse($church, 'Church created successfully.');
    }

    public function update(Request $request, $id)
    {
        $church = Church::find($id);
        $church->update($request->all());
        return $this->sendResponse($church, 'Church updated successfully.');
    }

    public function destroy($id)
    {
        $church = Church::find($id);
        $church->delete();
        return $this->sendResponse($church, 'Church deleted successfully.');
    }
}
