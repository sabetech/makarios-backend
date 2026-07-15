<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController;
use App\Models\User;
<<<<<<< HEAD
use Google\Client as GoogleClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseController
{
    /**
     * Handle Google OAuth ID token sign-in / sign-up
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function googleAuth(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        $idToken = $request->input('id_token');

        $clientId = config('services.google.client_id');
        if (!$clientId) {
            return $this->sendError('Google client id not configured.', ['error' => 'Missing GOOGLE_CLIENT_ID'], 500);
        }

        try {
            $googleClient = new GoogleClient(['client_id' => $clientId]);

            $payload = $googleClient->verifyIdToken($idToken);
        } catch (\Exception $e) {
            return $this->sendError('Google authentication failed.', ['error' => 'Invalid token or configuration'], 401);
        }
        if (!$payload) {
            return $this->sendError('Invalid Google ID token.', ['id_token' => 'Token verification failed'], 401);
        }

        if (empty($payload['email'])) {
            return $this->sendError('Google account has no email.', ['error' => 'Email not provided by Google'], 422);
        }

        $email = strtolower($payload['email']);
        $name = $payload['name'] ?? explode('@', $email)[0];

        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => null,
                'img_url' => $payload['picture'] ?? null,
            ]);
        } else {
            $updateData = [];
            if (!empty($payload['name']) && $payload['name'] !== $user->name) {
                $updateData['name'] = $payload['name'];
            }
            if (!empty($payload['picture']) && $payload['picture'] !== $user->img_url) {
                $updateData['img_url'] = $payload['picture'];
            }
            if (!empty($updateData)) {
                $user->update($updateData);
            }
        }

        $user->roles;
        $user->getPermissionsViaRoles();
        $user->isLeaderOf = $user->isLeaderOf();

        $token = $user->createToken('makarios-pwa')->plainTextToken;

        $success = [
            'token' => $token,
            'user' => $user,
        ];

        return $this->sendResponse($success, 'User authenticated with Google successfully.');
=======
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

            $user->phone = $request->phone;

            if ($request->hasFile('image')) {
                $file = $request->file('image');

                $result = Cloudinary::upload($file->getRealPath(), [
                    'folder' => 'app-users',
                    'public_id' => 'user_' . $user->id . '_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'overwrite' => true,
                    'resource_type' => 'image',
                ]);

                $user->img_url = $result->getSecurePath();
            }

            $user->save();

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
>>>>>>> 3f5a7ad (feat: implement Google sign-in authentication)
    }
}
