<?php

namespace App\Http\Controllers\API\V2;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Member;
use App\Models\Bacenta;
use App\Models\Basonta;
use App\Http\Controllers\API\BaseController as BaseController;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Log;

class MemberController extends BaseController {
    //
    public function index()
    {
        //Based on who is logged in, return the members that belong to the church/region/bacenta that the user belongs to.
        //Get Logged in user
        $user = Auth::user();

        if (!$user) {
            return $this->sendError('Unauthorised.', ['error'=>'User not found'], 401);
        }

        $members = Member::select();

        return $this->sendResponse($members->get(), 'Members retrieved successfully.');
    }

    public function create(Request $request){

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'picture' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'dob' => 'nullable|date',
            'gender' => 'nullable|array',
            'gender.*' => 'string|in:male,female',
            'marital_status' => 'nullable|array',
            'marital_status.*' => 'string|in:single,married',
            'occupation' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'gps_location' => 'nullable|string',
            'bacenta' => 'nullable|array',
            'bacenta.*' => 'integer|exists:bacentas,id',
        ]);

        $bacenta = isset($validated['bacenta'][0]) ? Bacenta::find($validated['bacenta'][0]) : null;

        $data = [
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? null,
            'email' => $validated['email'] ?? null,
            'date_of_birth' => isset($validated['dob']) ? date('Y-m-d', strtotime($validated['dob'])) : null,
            'gender' => isset($validated['gender'][0]) ? $validated['gender'][0] : null,
            'marital_status' => isset($validated['marital_status'][0]) ? $validated['marital_status'][0] : null,
            'occupation' => $validated['occupation'] ?? null,
            'address' => $validated['address'] ?? null,
            'bacenta_id' => isset($validated['bacenta'][0]) ? $validated['bacenta'][0] : null,
            'basonta_id' => isset($validated['basonta'][0]) ? $validated['basonta'][0] : null,
            'stream_id' => $bacenta?->region?->stream_id,
        ];

        if (!empty($validated['picture'])) {
            $result = Cloudinary::upload($validated['picture'], [
                'folder' => 'members',
                'resource_type' => 'image',
            ]);
            $data['img_url'] = $result->getSecurePath();
        }

        $member = Member::create($data);

        return $this->sendResponse($member, 'Member created successfully.');
    }
}