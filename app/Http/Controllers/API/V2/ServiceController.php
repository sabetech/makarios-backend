<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class ServiceController extends BaseController
{
    //
    public function index(): JsonResponse {
        // Get recent services
        $services = Service::with('serviceType')->orderBy('created_at', 'desc')->take(5)->get();

        //filter services based on user role
        $user = Auth::user();
        if ($user->hasRole(['Super Admin', 'Bishop'])) {
            // See all
        } elseif ($user->hasRole('Stream Lead')) {
            $services = $services->whereHas('stream', function($q) use ($user) {
                $q->whereHas('leads', function($q2) use ($user) {
                    $q2->where('id', $user->id);
                });
            });
        } elseif ($user->hasRole('Region Lead')) {
            $services = $services->whereHas('region', function($q) use ($user) {
                $q->whereHas('leads', function($q2) use ($user) {
                    $q2->where('id', $user->id);
                });
            });
        } elseif ($user->hasRole('Bacenta Leader')) {
            $services = $services->whereHas('bacenta', function($q) use ($user) {
                $q->whereHas('leaders', function($q2) use ($user) {
                    $q2->where('id', $user->id);
                });
            });
        } else {
            return $this->sendError('Unauthorized', [], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $services
        ]);
    }
}
