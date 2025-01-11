<?php

namespace App\Http\Controllers\API;

use App\Models\ServiceType;
use App\Models\Service;
use App\Models\Stream;
use App\Models\Bacenta;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ServiceController extends BaseController
{
    //
    public function serviceTypes() {
        $user = Auth::user();

        if ($user) {
            $role = $user->roles[0];
        } else {
            return $this->sendError('Unauthorised.', ['error'=>'User not found'], 401);
        }

        $serviceTypes = ServiceType::where('role_id', '>=', $role->id)->with(['role', 'church'])->get();

        return $this->sendResponse($serviceTypes, 'Service Types retrieved successfully.');
    }

    public function create(Request $request) {
        $user = Auth::user();

        if ($user) {
            $role = $user->roles[0];
            Log::info("BalanceRole: " . $role);
        } else {
            return $this->sendError('Unauthorised.', ['error'=>'User not found'], 401);
        }

        Log::info(["Request:: " => $request->all()]);

        $date = $request->get('date');
        $serviceType = $request->get('service_type');
        $offering = $request->get('offering');
        $foreignCurrency = $request->get('foreign_currency');
        $treasurers = $request->get('treasurers');
        $attendance = $request->get('attendance');

        if ($request->hasFile('treasurers_picture')) {
            $file = $request->file('treasurers_picture');
            $result = Cloudinary::upload($file->getRealPath(), [
                'folder' => 'services/treasurers',
                'public_id' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'overwrite' => false,
                'resource_type' => 'image',
            ]);

            $treasurers_img_url = $result->getSecurePath();
        }else {
            return $this->sendError('Treasurers picture not found.', ['error'=>'Treasurers picture not found'], 403);
        }

        if ($request->hasFile('service_image')) {
            $file = $request->file('service_image');
            $result = Cloudinary::upload($file->getRealPath(), [
                'folder' => 'services/service_photos',
                'public_id' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'overwrite' => false,
                'resource_type' => 'image',
            ]);

            $service_img_url = $result->getSecurePath();

        }else{
            return $this->sendError('Service picture not found.', ['error'=>'Service picture not found'], 403);
        }


        $service = new Service();
        $service->date = $date;
        $service->service_type_id = $serviceType;

        if (ServiceType::find($serviceType)->service_type == 'Bacenta Service') {
            $service->bacenta_id = $request->get('bacenta_id');
            $bacenta = Bacenta::find($service->bacenta_id);
            if ($bacenta) {
                $service->zone_id = $bacenta->zone->id;
                $service->region_id = $bacenta->region->id;
                $service->stream_id = $bacenta->region->stream->id;
                $service->church_id = $bacenta->region->stream->church->id;
            }else{
                return $this->sendError("Bacenta not found");
            }
        }

        if ($user->roles[0]->name === 'Zone Lead') {
            if ($zone = $user->zone) {
                $service->zone_id = $zone->id;
                $service->region_id = $zone->region->id;
                $service->stream_id = $zone->region->stream->id;
                $service->church_id = $zone->region->stream->church->id;
            }else{
                return $this->sendError("Zone not Found");
            }
        }

        if ($user->roles[0]->name === 'Region Lead') {
            if ($region = $user->region) {
                $service->region_id = $region->id;
                $service->stream_id = $region->stream->id;
                $service->church_id = $region->stream->church->id;
            }else{
                return $this->sendError('Region not found');
            }
        }

        if ($user->roles[0]->name === 'Stream Lead') {
            if ($stream = $user->stream) {
                $service->stream_id = $stream->id;
                $service->church_id = $stream->church->id;
            }else{
                return $this->sendError('Stream not found');
            }
        }

        $service->church_id = 1; //FIX THIS ASAP ... THIS IS NOT RIGHT

        $service->offering = $offering;
        $service->foreign_currency = $foreignCurrency == 'undefined' ? null : $foreignCurrency;
        $service->treasurers = $treasurers;
        $service->attendance = $attendance;
        $service->treasurer_photo = $treasurers_img_url;
        $service->service_photo = $service_img_url;

        $service->save();

        return $this->sendResponse($service, 'Service Type created successfully.');
    }

    public function index(Request $request) {

        $user = Auth::user(); //This is the user that is logged in and use for data filtering

        $streamId = $request->get('stream_id', null);
        if ($streamId && $streamId != 0) { //This is either a Sunday Service, Jesus XP or 1st Service Sunday
            $stream = Stream::find($streamId);
            if ($stream) {
                $streamServiceType = ServiceType::where('service_type', 'Stream Service')->first();
                $services = $stream->services()
                                ->where('stream_id', $streamId)
                                ->where('service_type_id', $streamServiceType->service_type)
                                ->with(['serviceType', 'church', 'bacenta', 'zone', 'region'])->get();
                return $this->sendResponse($services, 'Services retrieved successfully.');
            }
        }

        if ($user) {

            if ($user->roles->count() > 0 && $user->roles[0]->name == 'Super Admin') {
                $services = Service::with(['serviceType', 'church', 'bacenta', 'zone', 'region'])->get();
                return $this->sendResponse($services, 'Services retrieved successfully.');
            }

            if ($stream = $user->stream) {
                $services = $stream->services()->with(['serviceType', 'church', 'bacenta', 'zone', 'region'])->get();
                return $this->sendResponse($services, 'Services retrieved successfully.');
            }

            if ($region = $user->region) {
                $services = $region->services()->with(['serviceType', 'church', 'bacenta', 'zone', 'region'])->get();
                return $this->sendResponse($services, 'Services retrieved successfully.');
            }

            if ($zone = $user->zone) {
                $services = $zone->services()->with(['serviceType', 'church', 'bacenta', 'zone', 'region'])->get();
                return $this->sendResponse($services, 'Services retrieved successfully.');
            }

            if ($bacenta = $user->bacenta) {
                $services = $bacenta->services()->with(['serviceType', 'church', 'bacenta', 'zone', 'region'])->get();
                return $this->sendResponse($services, 'Services retrieved successfully.');
            }

        }

        return $this->sendError('You are not authorized to view this page.');

    }

}
