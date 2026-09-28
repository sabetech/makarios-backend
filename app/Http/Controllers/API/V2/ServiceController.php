<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Bacenta;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\Stream;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;

class ServiceController extends BaseController
{
    //
    public function index(Request $request): JsonResponse {
        //get service type from param
        $serviceTypeId = $request->get('service_type_id');
        $from = $request->get('from');
        $to = $request->get('to');

        if ($serviceTypeId) {
            $services = Service::with('serviceType')
                ->where('service_type_id', $serviceTypeId)
                ->orderBy('created_at', 'desc')
                ->take(5);
                
        } else {
            $services = Service::with('serviceType')
                ->orderBy('created_at', 'desc')
                ->take(5);
        }

        //filter services based on user role
        $user = Auth::user();
        if ($user->hasRole(['Super Admin', 'General Admin', 'Bishop'])) {
            // See all
        } 
        if ($user->hasRole('Stream Lead')) {
            $services = $services->whereHas('stream', function($q) use ($user) {
                $q->whereHas('overseer', function($q2) use ($user) {
                    $q2->where('id', $user->id);
                });
            });
        } 
        if ($user->hasRole('Region Lead')) {
            $services = $services->whereHas('region', function($q) use ($user) {
                $q->whereHas('leader', function($q2) use ($user) {
                    $q2->where('id', $user->id);
                });
            });
        } 
        if ($user->hasRole('Bacenta Leader')) {
            $services = $services->whereHas('bacenta', function($q) use ($user) {
                $q->whereHas('leader', function($q2) use ($user) {
                    $q2->where('id', $user->id);
                });
            });
        } 

        if ($from && $to) {
            $services = $services->whereBetween('date', [$from, $to]);
        }

        

        

        

        return response()->json([
            'success' => true,
            'data' => $services->get()
        ]);
    }

    public function getTypes(): JsonResponse {
        $types = ServiceType::with('role')->get();

        //filter types based on user role
        $user = Auth::user();
        if ($user->hasRole(['Super Admin', 'General Admin', 'Bishop'])) {
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
        $serviceType = ServiceType::find(request()->input('service_type_id'));
        $typeName = $serviceType ? trim((string) $serviceType->service_type) : '';
        $isStreamService = strcasecmp($typeName, 'Stream Service') === 0;
        $isBacentaService = strcasecmp($typeName, 'Bacenta Service') === 0;

        $rules = [
            'service_type_id' => 'required|exists:service_types,id',
            'offering' => 'required|numeric',
            'foreign_currency' => 'nullable|numeric',
            'attendance' => 'required|integer',
            'service_date' => 'required|date',
            'treasures' => 'required|array',
            'treasures_picture' => 'required|string',
            'service_img' => 'required|string',
        ];

        if ($isStreamService) {
            $rules['stream_id'] = 'required|exists:streams,id';
            $rules['bacenta_id'] = 'prohibited';
        } elseif ($isBacentaService) {
            $rules['bacenta_id'] = 'required|exists:bacentas,id';
            $rules['stream_id'] = 'nullable|exists:streams,id';
        } else {
            $rules['bacenta_id'] = 'required_without:stream_id|nullable|exists:bacentas,id';
            $rules['stream_id'] = 'required_without:bacenta_id|nullable|exists:streams,id';
        }

        $messages = [];
        if ($isStreamService) {
            $messages = [
                'bacenta_id.prohibited' => 'Bacenta must not be present for Stream services.',
                'stream_id.required' => 'A stream is required for Stream services.',
            ];
        }

        $data = request()->validate($rules, $messages);

        if ($isStreamService) {
            $stream = Stream::find($data['stream_id']);
            if (!$stream) {
                return response()->json(['success' => false, 'message' => 'Stream not found.'], 422);
            }
            if (empty($stream->church_id)) {
                return response()->json(['success' => false, 'message' => 'Stream has no church assigned.'], 422);
            }
            $data['church_id'] = $stream->church_id;
            $data['bacenta_id'] = null;
            $data['region_id'] = null;
            $data['zone_id'] = null;
        } elseif ($isBacentaService || !empty($data['bacenta_id'])) {
            $bacenta = Bacenta::with('region.stream')->find($data['bacenta_id'] ?? null);
            if (!$bacenta || !$bacenta->region || !$bacenta->region->stream_id) {
                return response()->json(['success' => false, 'message' => 'Bacenta has no region/stream assigned.'], 422);
            }
            $derivedStreamId = (int) $bacenta->region->stream_id;
            if (!empty($data['stream_id']) && (int) $data['stream_id'] !== $derivedStreamId) {
                return response()->json([
                    'success' => false,
                    'message' => 'The stream does not match the bacenta hierarchy.',
                    'errors' => ['stream_id' => ['The stream does not match the bacenta hierarchy.']],
                ], 422);
            }
            $data['stream_id'] = $derivedStreamId;
            $data['region_id'] = $bacenta->region_id;
            $data['zone_id'] = $bacenta->zone_id;
            $stream = $bacenta->relationLoaded('region') && $bacenta->region->relationLoaded('stream')
                ? $bacenta->region->stream
                : Stream::find($derivedStreamId);
            if (!$stream || empty($stream->church_id)) {
                return response()->json(['success' => false, 'message' => 'Stream has no church assigned.'], 422);
            }
            $data['church_id'] = $stream->church_id;
        } else {
            $stream = Stream::find($data['stream_id']);
            if (!$stream) {
                return response()->json(['success' => false, 'message' => 'Stream not found.'], 422);
            }
            if (empty($stream->church_id)) {
                return response()->json(['success' => false, 'message' => 'Stream has no church assigned.'], 422);
            }
            $data['church_id'] = $stream->church_id;
            $data['region_id'] = null;
            $data['zone_id'] = null;
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

        $data['treasurers'] = implode(', ', $data['treasures']);
        unset($data['treasures']);
        $service = Service::create($data);

        return response()->json([
            'success' => true,
            'data' => $service
        ], 201);
    }
}
