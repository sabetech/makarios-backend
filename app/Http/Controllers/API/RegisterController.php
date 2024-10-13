<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Http\Controllers\API\BaseController as BaseController;


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
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        try {
            $user = User::create($input);
            $user->assignRole('Overseer');
            // if ($input['email'] == "albert.mensahansah@gmail.com") {
            //     $user->assignRole('Super Admin');
            // }

        }catch (Exception $e) {
            return $this->sendError('User Registration Error:', $e->getMessage());
        }

        $success['token'] =  $user->createToken('makarios-pwa')->plainTextToken;
        $success['name'] =  $user->name;

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
            $success['user'] =  $user;

            return $this->sendResponse($success, 'User login successfully.');
        }
        else{
            return $this->sendError('Unauthorised.', ['error'=>'User Credentials Incorrect'], 401);
        }
    }

    public function logout(): JsonResponse
    {
        $user = Auth::user()->tokens()->delete();
        return $this->sendResponse($user, 'User logout successfully.');
    }
}
