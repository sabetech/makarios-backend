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
                    $bacentaIds = $user->zone->bacentas()->pluck('id')->toArray();
                    $members = $members->whereIn('bacenta_id', $bacentaIds);
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
            ]);

            $imageUrl = $result->getSecurePath();
        }else {
            $imageUrl = $request->get('img_url');
        }

        $bacenta = Bacenta::find($bacenta_id);
        $region_id = null;
        $stream_id = null;
        if ($bacenta) {
            $region = $bacenta->region;
            $region_id = $region->id;
            $stream_id = $region->stream->id;

        }

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
