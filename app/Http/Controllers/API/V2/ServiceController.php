<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Bacenta;
use App\Models\Service;
use App\Models\ServiceType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

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

    public function getTypes(): JsonResponse {
        $types = ServiceType::with('role')->get();

        //filter types based on user role
        $user = Auth::user();
        if ($user->hasRole(['Super Admin','Bishop'])) {
            // See all
        } else {
            $types = $types->filter(function($type) use ($user) {
                return $user->roles[0]->rank >= $type->role->rank;
            })->values();
        }

        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }

    public function create(): JsonResponse {
        $data = request()->validate([
            'service_type_id' => 'required|exists:service_types,id',
            'bacenta_id' => 'required|exists:bacentas,id',
            'offering' => 'required|numeric',
            'attendance' => 'required|integer',
            'service_date' => 'required|date',
            'treasures' => 'required|array',
            'treasures_picture' => 'required|string',
            'service_img' => 'required|string',
        ]);

        if ($data['bacenta_id']) {
            $bacenta = Bacenta::with('region')->find($data['bacenta_id']);
            if ($bacenta && $bacenta->region) {
                $data['region_id'] = $bacenta->region_id;
                $data['stream_id'] = $bacenta->region->stream_id;
            }
            if ($bacenta && $bacenta->zone_id) {
                $data['zone_id'] = $bacenta->zone_id;
            }
        }

        $treasurerResult = Cloudinary::upload($data['treasures_picture'], [
            'folder' => 'services/treasurers',
            'resource_type' => 'image',
        ]);
        $data['treasurer_photo'] = $treasurerResult->getSecurePath();
        unset($data['treasures_picture']);

        $serviceImgResult = Cloudinary::upload($data['service_img'], [
            'folder' => 'services/service_photos',
            'resource_type' => 'image',
        ]);
        $data['service_photo'] = $serviceImgResult->getSecurePath();
        unset($data['service_img']);

        $data['date'] = $data['service_date'];
        unset($data['service_date']);

        $data['church_id'] = 1 ; // Assuming all services are for the same church for now. Adjust as needed.
        $data['treasurers'] = implode(', ', $data['treasures']);
        $service = Service::create($data);

        return response()->json([
            'success' => true,
            'data' => $service
        ], 201);
    }
}
