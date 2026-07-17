<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Exception;

class AuthController extends BaseController
{
    public function googleAuth(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        try {
            $googleUser = $this->getGoogleUserInfo($request->token);

            if (!$googleUser) {
                return $this->sendError('Invalid Google token.', ['error' => 'Could not verify Google token'], 401);
            }

            $isNewUser = false;
            $user = User::where('provider', 'google')
                ->where('provider_id', $googleUser['sub'])
                ->first();

            if (!$user) {
                $user = User::where('email', $googleUser['email'])->first();

                if ($user) {
                    $user->update([
                        'provider' => 'google',
                        'provider_id' => $googleUser['sub'],
                        'img_url' => $user->img_url ?? $googleUser['picture'] ?? null,
                    ]);
                } else {
                    $user = User::create([
                        'name' => $googleUser['name'] ?? $googleUser['email'],
                        'email' => $googleUser['email'],
                        'provider' => 'google',
                        'provider_id' => $googleUser['sub'],
                        'img_url' => $googleUser['picture'] ?? null,
                        'password' => null,
                    ]);
                    $isNewUser = true;
                }
            }

            if ($isNewUser && empty($user->phone)) {
                $isNewUser = true;
                $user->assignRole('Bacenta Leader');
            } elseif ($isNewUser && !empty($user->phone)) {
                $isNewUser = false;
            }

            $token = $user->createToken('makarios-pwa')->plainTextToken;
            $user->roles;
            $user->getPermissionsViaRoles();
            $user->isLeaderOf = $user->isLeaderOf();

            return $this->sendResponse([
                'token' => $token,
                'user' => $user,
                'is_new_user' => $isNewUser,
            ], 'Google login successful.');

        } catch (Exception $e) {
            Log::error('Google auth error: ' . $e->getMessage());
            return $this->sendError('Google authentication failed.', ['error' => $e->getMessage()], 401);
        }
    }

    public function completeProfile(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        try {
            $user = $request->user();

            if (!$user) {
                return $this->sendError('User not authenticated.', ['error' => 'No authenticated user found'], 401);
            }

            $user->phone = $request->phone;

            if ($request->hasFile('image')) {
                try {
                    $file = $request->file('image');

                    $result = Cloudinary::upload($file->getRealPath(), [
                        'folder' => 'app-users',
                        'public_id' => 'user_' . $user->id . '_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                        'overwrite' => true,
                        'resource_type' => 'image',
                    ]);

                    $user->img_url = $result->getSecurePath();
                } catch (Exception $e) {
                    Log::warning('Cloudinary upload failed: ' . $e->getMessage());
                }
            }

            $user->save();

            //make the user a bacenta leader by default
            $user->roles;
            $user->getPermissionsViaRoles();
            $user->isLeaderOf = $user->isLeaderOf();

            return $this->sendResponse([
                'user' => $user,
            ], 'Profile completed successfully.');

        } catch (Exception $e) {
            Log::error('Complete profile error: ' . $e->getMessage());
            return $this->sendError('Failed to complete profile.', ['error' => $e->getMessage()], 500);
        }
    }

    private function getGoogleUserInfo(string $accessToken): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();

        if (!isset($data['sub']) || !isset($data['email'])) {
            return null;
        }

        return $data;
    }
}
