<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController;
use App\Models\ImpersonationLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * "Ghost mode": lets a Super Admin act as another user to see what they see
 * and fix their issues. Each session gets its own short-lived Sanctum token
 * for the target user and an audit row in impersonation_logs.
 */
class ImpersonationController extends BaseController
{
    public const TOKEN_PREFIX = 'impersonated-by:';
    public const TTL_MINUTES = 120;

    public function start(Request $request, int $id): JsonResponse
    {
        $admin = $request->user();
        $target = User::find($id);

        if (!$target) {
            return $this->sendError('User not found.', [], 404);
        }
        if ($target->id === $admin->id) {
            return $this->sendError('You cannot ghost into your own account.', [], 403);
        }
        if ($target->hasRole('Super Admin')) {
            return $this->sendError('Super Admin accounts cannot be ghosted.', [], 403);
        }

        $newToken = $target->createToken(
            self::TOKEN_PREFIX . $admin->id,
            ['*'],
            now()->addMinutes(self::TTL_MINUTES)
        );

        ImpersonationLog::create([
            'impersonator_id' => $admin->id,
            'impersonated_id' => $target->id,
            'token_id' => $newToken->accessToken->id,
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'started_at' => now(),
        ]);

        Log::info("Impersonation started: admin {$admin->id} as user {$target->id}");

        // Same user shape as the login endpoints.
        $target->roles;
        $target->getPermissionsViaRoles();
        $target->isLeaderOf = $target->isLeaderOf();

        return $this->sendResponse([
            'token' => $newToken->plainTextToken,
            'user' => $target,
            'impersonating' => true,
            'expires_at' => $newToken->accessToken->expires_at,
        ], 'Ghost session started.');
    }

    public function stop(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if (!$token || !Str::startsWith((string) $token->name, self::TOKEN_PREFIX)) {
            return $this->sendError('Not a ghost session.', [], 400);
        }

        ImpersonationLog::where('token_id', $token->id)
            ->whereNull('ended_at')
            ->update(['ended_at' => now()]);

        Log::info("Impersonation ended: token {$token->id} on user {$request->user()->id}");

        $token->delete();

        return $this->sendResponse([], 'Ghost session ended.');
    }
}
