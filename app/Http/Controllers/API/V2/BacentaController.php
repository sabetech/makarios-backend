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

        $query = Bacenta::with(['leader', 'region.stream', 'zone'])
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
        } elseif ($user->hasRole('Bacenta Leader')) {
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
            'region_id' => 'required|exists:regions,id',
        ]);

        $bacenta = Bacenta::create($request->only(['name', 'leader_id', 'zone_id', 'region_id']));

        return $this->sendResponse($bacenta, 'Bacenta created successfully.');
    }

    public function show($id)
    {
        [$bacenta, $error] = $this->getAccessibleBacenta($id);
        if ($error) {
            return $error;
        }

        $recentServices = $bacenta->services()
            ->orderByDesc('date')
            ->limit(4)
            ->get(['id', 'bacenta_id', 'date', 'attendance', 'offering']);

        $bacenta->setRelation('recent_services', $recentServices);

        return $this->sendResponse($bacenta->load(['leader', 'zone', 'region.stream', 'location'])->loadCount('members'), 'Bacenta retrieved successfully.');
    }

    public function members(Request $request, $id)
    {
        [$bacenta, $error] = $this->getAccessibleBacenta($id);
        if ($error) {
            return $error;
        }

        $members = $bacenta->members()
            ->orderBy('name')
            ->get();

        return $this->sendResponse($members, 'Bacenta members retrieved successfully.');
    }

    public function suspend($id)
    {
        return $this->setActiveState($id, false, 'Bacenta suspended successfully.');
    }

    public function activate($id)
    {
        return $this->setActiveState($id, true, 'Bacenta activated successfully.');
    }

    private function setActiveState($id, bool $active, string $message)
    {
        $user = auth()->user();
        $bacenta = Bacenta::find($id);
        if (!$bacenta) {
            return $this->sendError('Bacenta not found', [], 404);
        }

        if ($user->hasRole('Region Lead') && (!$user->region || $bacenta->region_id !== $user->region->id)) {
            return $this->sendError('Unauthorized', ['error' => 'You can only manage bacentas in your region.'], 403);
        }

        $bacenta->update(['is_active' => $active]);

        return $this->sendResponse($bacenta, $message);
    }

    /**
     * Resolve a bacenta the current user may view, following the same
     * scoping rules as index().
     *
     * @return array{0: Bacenta|null, 1: \Illuminate\Http\JsonResponse|null}
     */
    private function getAccessibleBacenta($id): array
    {
        $user = auth()->user();
        $bacenta = Bacenta::find($id);

        if (!$bacenta) {
            return [null, $this->sendError('Bacenta not found', [], 404)];
        }

        if ($user->hasRole(['Super Admin', 'Bishop'])) {
            return [$bacenta, null];
        }

        if ($user->hasRole('Region Lead')) {
            if ($user->region && $bacenta->region_id === $user->region->id) {
                return [$bacenta, null];
            }
            return [null, $this->sendError('Unauthorized', ['error' => 'You do not have access to this bacenta.'], 403)];
        }

        if ($user->hasRole('Bacenta Leader')) {
            if ($bacenta->leader_id === $user->id) {
                return [$bacenta, null];
            }
            return [null, $this->sendError('Unauthorized', ['error' => 'You do not have access to this bacenta.'], 403)];
        }

        return [null, $this->sendError('Unauthorized', [], 403)];
    }
}
