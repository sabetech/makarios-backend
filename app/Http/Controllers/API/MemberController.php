<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use App\Models\Member;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Bacenta;

class MemberController extends BaseController
{

    public function index(Request $request){
        $members = Member::select();
        $user = Auth::user();

        $streamId = $request->get('stream_id', null);
        $regionId = $request->get('region_id', null);
        $bacentaId = $request->get('bacenta_id', null);
        // $churchId = $request->get('church_id', null); TODO:: Include this if we start rolling out for multiple churches

        if ($streamId) {
            $members = $members->where('stream_id', $streamId);
        }

        if ($regionId) {
            $members = $members->where('region_id', $regionId);
        }

        if ($bacentaId) {
            $members = $members->where('bacenta_id', $bacentaId);
        }

        if ($user) {
            if ($user->roles->count() > 0) {
                if (($user->roles[0]->name) == 'Region Lead' ) {
                    $members = $members->where('region_id', $user->region->id);
                }

                if (($user->roles[0]->name) == 'Zone Lead') {
                    $members = $members->where('zone_id', $user->zone->id)
                                        ->orWhereIn('bacenta_id', $user->zone->bacentas->pluck('id')->toArray());
                }

                if (($user->roles[0]->name) == 'Bacenta Leader') {
                    $members = $members->where('bacenta_id', $user->bacenta->id);
                }
            }
        }

        return $this->sendResponse($members->get(), 'Members retrieved successfully.');
    }


    //
    public function create(Request $request){

        $user = Auth::user();

        Log::info("member info", [$request->all()]);

        Log::info("User Adding Member:", ["User" => $request->user()]);

        $name = $request->get('member_name');
        $date_of_birth = $request->get('date_of_birth');
        $phone = $request->get('phone', null);
        $whatsapp = $request->get('whatsapp', null);
        $gender = $request->get('gender',  null);
        $email = $request->get('email', null);
        $address = $request->get('address', null);
        $occupation = $request->get('occupation', null);
        $marital_status = $request->get('marital_status', null);
        $bacenta_id = $request->get('bacenta_id', null);
        $basonta_id = $request->get('basonta_id', null);

        $file = $request->file('picture', null);

        if ($request->hasFile('picture')) {
            $file = $request->file('picture');
            $result = Cloudinary::upload($file->getRealPath(), [
                'folder' => 'members',
                'public_id' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'overwrite' => false,
                'resource_type' => 'image',
                'transformation' => [
                    'quality' => 'auto:eco',
                    'fetch_format' => 'auto',
                ]
            ]);

            $imageUrl = $result->getSecurePath();
        }else {
            $imageUrl = $request->get('img_url');
        }

        if (!$imageUrl) {
            return $this->sendError('Image not found', ['error' => 'Image Could not be uploaded, Please Try again'], 404);
        }

        $region_id = null;
        $stream_id = null;
        $zone_id = null;

        $bacenta = Bacenta::find($bacenta_id);

        if (!$bacenta) {
            //use the user's region as the default
            Log::info("No Bacenta ID Found: Using User's info::");

            $role = $user->roles[0];
            if ($role->name == 'Bishop' || $role->name == 'Stream Lead' || $role->name == 'Stream Admin') {
                $stream_id = $user->stream->id;
            }

            if ($role->name == 'Region Lead') {
                $region_id = $user->region->id;
                $stream_id = $user->region->stream->id;
            }

            if ($role->name == 'Zone Lead') {
                $zone_id = $user->zone->id;
                $stream_id = $user->zone->stream->id;
                $region_id = $user->zone->region->id;
            }

            if ($role->name == 'Bacenta Leader') {
                $bacenta_id = $user->bacenta->id;
                $stream_id = $user->bacenta->region->stream->id;
                $region_id = $user->bacenta->region->id;

                if ($zone = $user->bacenta->zone) {
                    $zone_id = $zone->id;
                }

            }
        }else{

            $region = $bacenta->region;

            if ($zone = $bacenta->zone) {
                $zone_id = $zone->id;
            }

            $region_id = $region->id;
            $stream_id = $region->stream->id;

        }

        Log::info("Bacenta ID: " . $bacenta_id . " Zone ID: " . $zone_id . " Region ID: " . $region_id . " Stream ID: " . $stream_id);

        $location = null;
        if ($request->get('location')) {
            //TODO:: add additional fields to location to distinguish if location is for a member or for a council or bacenta so I don't get duplicated location rows
            //TODO:: Just Redesign the location table. It's a good but needs to be redesigned
            //Holy Spirt, You are my co programmer!

            $lat_lng = $request->get('location');
            $location = Location::create([
                'lat_lng' => $lat_lng,
                'name' => $user->region->name ?? "N/A",
                'address' => $request->get('address', null),
            ]);
        }

        $user = self::checkifMemberIsAlreadyRegistered($email);

        $member = Member::create([
            'name' => $name,
            'date_of_birth' => $date_of_birth,
            'phone' => $phone,
            'whatsapp' => $whatsapp,
            'gender' => $gender,
            'email' => $email,
            'address' => $address,
            'occupation' => $occupation,
            'marital_status' => $marital_status,
            'img_url' => $imageUrl,
            'region_id' => $region_id,
            'zone_id' => $zone_id,
            'bacenta_id' => intval($bacenta_id) === 0 ? null : $bacenta_id,
            'basonta_id' => intval($basonta_id) === 0 ? null : $basonta_id,
            'stream_id' => $stream_id,
            'location_id' => $location->id ?? null,
            'user_id' => $user->id ?? null
        ]);

        return $this->sendResponse($member, 'Member created successfully.');

    }

    public function checkifMemberIsAlreadyRegistered($email) {

        $user = User::where('email', $email)->first();
        return $user;

    }


}
