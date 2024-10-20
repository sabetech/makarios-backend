<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use App\Models\Member;
use App\Models\Church;
use App\Models\Location;
use App\Models\User;
use Log;
use Illuminate\Support\Facades\Auth;

class MemberController extends BaseController
{

    public function index(){
        $members = Member::select();
        if (Auth::user()->roles->count() > 0) {
            if ((Auth::user()->roles[0]->name) == 'Overseer' ) {
                $members = $members->where('council_id', Auth::user()->council->id);
            }

            if ((Auth::user()->roles[0]->name) == 'Bacenta Leader') {
                $members = $members->where('bacenta_id', Auth::user()->bacenta->id);
            }

        }
        return $this->sendResponse($members->get(), 'Members retrieved successfully.');
    }


    //
    public function create(Request $request){

        $user = Auth::user();

        Log::info($request->all());

        Log::info("user?", ["User" => $request->user()]);

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

        $council_id = null;
        if ($user->council) {
            $council_id = $user->council->id;
        }

        $location = null;
        if ($request->get('location')) {
            //TODO:: add additional fields to location to distinguish if location is for a member or for a council or bacenta so I don't get duplicated location rows
            //TODO:: Just Redesign the location table. It's a good but needs to be redesigned
            //Holy Spirt, You are my co programmer!


            $lat_lng = $request->get('location');
            $location = Location::create([
                'lat_lng' => $lat_lng,
                'name' => $user->council->name ?? "N/A",
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
            'council_id' => $council_id,
            'bacenta_id' => intval($bacenta_id) === 0 ? null : $bacenta_id,
            'basonta_id' => intval($basonta_id) === 0 ? null : $bacenta_id,
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
