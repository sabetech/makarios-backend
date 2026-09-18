<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Arrival;
use App\Models\Bacenta;
use Illuminate\Http\Request;

class ArrivalController extends BaseController
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $date = $request->get('date', date('Y-m-d'));

        // Query accessible bacentas based on role
        $bacentaQuery = Bacenta::with(['leader', 'region', 'zone']);

        if ($user->hasRole(['Super Admin', 'Bishop'])) {
            // All bacentas
        } elseif ($user->hasRole('Stream Lead')) {
            // Stream bacentas if applicable, or via regions
            $bacentaQuery->whereHas('region', function ($q) {
                // Stream filter if applicable
            });
        } elseif ($user->hasRole('Region Lead')) {
            $region = $user->region;
            if ($region) {
                $bacentaQuery->where('region_id', $region->id);
            } else {
                return $this->sendError('No region assigned', [], 403);
            }
        } elseif ($user->hasRole('Bacenta Leader')) {
            $bacentaQuery->where('leader_id', $user->id);
        } else {
            return $this->sendError('Unauthorized', [], 403);
        }

        $bacentas = $bacentaQuery->get();
        $bacentaIds = $bacentas->pluck('id')->toArray();

        // Get arrivals for the date and accessible bacentas
        $arrivals = Arrival::with(['bacenta.leader', 'bacenta.region'])
            ->whereIn('bacenta_id', $bacentaIds)
            ->where('date', $date)
            ->orderByDesc('created_at')
            ->get();

        $totalBussed = $arrivals->sum('number_bussed');

        // Create breakdown per Bacenta
        $breakdown = $bacentas->map(function ($bacenta) use ($arrivals) {
            $bacentaArrival = $arrivals->firstWhere('bacenta_id', $bacenta->id);
            return [
                'bacenta_id' => $bacenta->id,
                'bacenta_name' => $bacenta->name,
                'leader_name' => $bacenta->leader ? $bacenta->leader->name : 'N/A',
                'region_name' => $bacenta->region ? $bacenta->region->name : 'N/A',
                'number_bussed' => $bacentaArrival ? $bacentaArrival->number_bussed : 0,
                'filled' => !is_null($bacentaArrival),
                'arrival_id' => $bacentaArrival ? $bacentaArrival->id : null,
                'time' => $bacentaArrival ? $bacentaArrival->time : null,
                'date' => $bacentaArrival ? $bacentaArrival->date : null,
            ];
        });

        $userRole = 'Bacenta Leader';
        if ($user->hasRole(['Super Admin', 'Bishop'])) {
            $userRole = 'Admin';
        } elseif ($user->hasRole('Region Lead')) {
            $userRole = 'Region Lead';
        }

        return $this->sendResponse([
            'date' => $date,
            'total_bussed' => $totalBussed,
            'role' => $userRole,
            'arrivals' => $arrivals,
            'breakdown' => $breakdown,
        ], 'Arrivals retrieved successfully.');
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'bacenta_id' => 'required|exists:bacentas,id',
            'date' => 'required|date',
            'number_bussed' => 'required|integer|min:0',
            'time' => 'nullable|string',
        ]);

        $bacentaId = $request->input('bacenta_id');
        $bacenta = Bacenta::find($bacentaId);

        if (!$bacenta) {
            return $this->sendError('Bacenta not found', [], 404);
        }

        // Check authorization to submit for this bacenta
        if ($user->hasRole(['Super Admin', 'Bishop'])) {
            // Authorized
        } elseif ($user->hasRole('Region Lead')) {
            if (!$user->region || $bacenta->region_id !== $user->region->id) {
                return $this->sendError('Unauthorized', ['error' => 'You can only submit arrivals for bacentas in your region.'], 403);
            }
        } elseif ($user->hasRole('Bacenta Leader')) {
            if ($bacenta->leader_id !== $user->id) {
                return $this->sendError('Unauthorized', ['error' => 'You can only submit arrivals for your assigned bacenta.'], 403);
            }
        } else {
            return $this->sendError('Unauthorized', [], 403);
        }

        $date = $request->input('date');

        $time = $request->input('time') ?: date('H:i:s');

        // Update or create arrival for this bacenta and date
        // Include soft-deleted rows so re-submitting after a delete restores instead of duplicating
        $arrival = Arrival::withTrashed()->firstOrNew(
            [
                'bacenta_id' => $bacentaId,
                'date' => $date,
            ]
        );

        if ($arrival->exists && $arrival->trashed()) {
            $arrival->restore();
        }

        $arrival->fill([
            'time' => $time,
            'number_bussed' => $request->input('number_bussed'),
            'img_proof' => $request->input('img_proof', ''),
        ]);
        $arrival->save();

        return $this->sendResponse($arrival, 'Arrival submitted successfully.');
    }
}
