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
    //
    public function create(Request $request){

        $user = Auth::user();

        Log::info($request->all());

        $name = $request->get('member_name');
        $date_of_birth = $request->get('date_of_birth');
        $phone = $request->get('phone');
        $whatsapp = $request->get('whatsapp');
        $gender = $request->get('gender');
        $email = $request->get('email');
        $address = $request->get('address');
        $occupation = $request->get('occupation');
        $marital_status = $request->get('marital_status');
        $bacenta_id = $request->get('bacenta_id');
        $basonta_id = $request->get('basonta_id');

        $file = $request->file('picture');

        $result = Cloudinary::upload($file->getRealPath(), [
            'folder' => 'members',
            'public_id' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'overwrite' => false,
            'resource_type' => 'image',
        ]);

        $imageUrl = $result->getSecurePath();

        $council_id = $user->council->id;
        $location = null;
        if ($request->get('location')) {

            $lat_lng = $request->get('location');
            $location = Location::create([
                'lat_lng' => $lat_lng,
                'name' => $user->council->name,
                'address' => $request->get('address'),
            ]);
        }


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
            'bacenta_id' => $bacenta_id,
            'location_id' => $location->id ?? null,
        ]);

        return $this->sendResponse($member, 'Member created successfully.');

    }
}
