<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController;
use App\Models\User;
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
    }
}
