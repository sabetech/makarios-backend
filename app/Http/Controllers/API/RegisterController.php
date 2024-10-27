<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Http\Controllers\API\BaseController as BaseController;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Log;
use Member;

class RegisterController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $input['email'] = strtolower($input['email']);
        try {
            $user = User::create($input);

            if ($input['email'] == "makarioshq.church@gmail.com") {
                $user->assignRole('Super Admin');
            }

            self::checkIfUserIsMember($user);

        }catch (Exception $e) {
            return $this->sendError('User Registration Error:', $e->getMessage());
        }

        $user->roles;

        $success['token'] =  $user->createToken('makarios-pwa')->plainTextToken;
        $success['user'] =  $user;

        return $this->sendResponse($success, 'User register successfully.');
    }

    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request): JsonResponse
    {
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){
            $user = Auth::user();
            $success['token'] =  $user->createToken('makarios-pwa')->plainTextToken;
            $user->roles;
            $user->permissions = $user->getPermissionsViaRoles();
            $success['user'] =  $user;

            return $this->sendResponse($success, 'User login successfully.');
        }
        else{
            return $this->sendError('Unauthorised.', ['error'=>'User Credentials Incorrect'], 401);
        }
    }

    public function uploadPhoto(Request $request): JsonResponse {

        $file = $request->get('image');

        if ($request->hasFile('image')) {
            $file = $request->file('image');
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,jiff,jif|max:8192',
            'user_id' => 'required'
        ]);

        $result = Cloudinary::upload($file->getRealPath(), [
            'folder' => 'app-users',
            'public_id' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'overwrite' => false,
            'resource_type' => 'image',
        ]);

        // Get the URL of the uploaded image
        $imageUrl = $result->getSecurePath();

        $user = User::find($request->user_id);
        $user->roles;
        if ($user) {
            $user->update([
                'img_url' => $imageUrl
            ]);
        }else {
            return $this->sendError('User not found.', ['error'=>'User not found'], 404);
        }

        return $this->sendResponse($user, 'User photo uploaded successfully.');

    }

    public function logout(): JsonResponse
    {
        $user = Auth::user()->tokens()->delete();
        return $this->sendResponse($user, 'User logout successfully.');
    }

    public static function checkIfUserIsMember($user) {

        $member = Member::where('user_id', $user->id)->first();

        if ($member) {

            $member->update([
                'user_id' => $user->id
            ]);
        }

    }


}
