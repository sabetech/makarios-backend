<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Bacenta;
use Illuminate\Http\Request;

class BacentaController extends BaseController
{
    //
    public function index() {
        $user = auth()->user();

        $query = Bacenta::with(['leader', 'region.stream'])
            ->withCount('members');

        if ($user->hasRole(['Super Admin', 'Bishop'])) {
            // See all
        } elseif ($user->hasRole('Region Lead')) {
            $region = $user->region;
            if ($region) {
                $query->where('region_id', $region->id);
            } else {
                return $this->sendError('No region assigned', [], 403);
            }
        } elseif ($user->hasRole('Bacenta Lead')) {
            $query->where('leader_id', $user->id);
        } else {
            return $this->sendError('Unauthorized', [], 403);
        }

        $bacentas = $query->get();

        return $this->sendResponse($bacentas, 'Bacentas retrieved successfully.');
    }

    public function update(Request $request, $id)
    {
        $bacenta = Bacenta::find($id);
        if (!$bacenta) {
            return $this->sendError('Bacenta not found', [], 404);
        }

        // Authorization logic here (similar to index)

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'leader_id' => 'sometimes|exists:users,id',
            'region_id' => 'sometimes|exists:regions,id',
        ]);

        $bacenta->update(request()->only(['name', 'leader_id', 'region_id']));

        return $this->sendResponse($bacenta, 'Bacenta updated successfully.');

    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'leader_id' => 'required|exists:users,id',
            'zone_id' => 'required|exists:zones,id',
            'region_id' => 'required|exists:regions,id',
        ]);

        $bacenta = Bacenta::create($request->only(['name', 'leader_id', 'zone_id', 'region_id']));

        return $this->sendResponse($bacenta, 'Bacenta created successfully.');
    }   
}
