<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class UserController extends BaseController
{
    public function index() {
        $users = User::with(['roles', 'region', 'bacenta', 'overseenStreams'])
            ->get()
            ->map(function($user) {
                $userArray = $user->toArray();
                $role = $user->roles->first()->name ?? null;
                $userArray['role'] = $role;
                if ($role == 'Stream Lead') {
                    $userArray['leading'] = [
                        "name" => $user->overseenStreams->pluck('name')->join(', ') ?? null,
                        "type" => "Stream",
                    ];
                } elseif ($role == 'Region Lead') {
                    $userArray['leading'] = [
                        "name" => $user->region->name ?? null,
                        "type" => "Region",
                    ];
                } elseif ($role == 'Bacenta Leader') {
                    $userArray['leading'] = [
                        "name" => $user->bacenta->name ?? null,
                        "type" => "Bacenta",
                    ];
                } else {
                    $userArray['leading'] = null;
                }
                unset($userArray['roles'], $userArray['region'], $userArray['bacenta'], $userArray['overseen_streams']);
                return $userArray;
            });

        return $this->sendResponse($users, 'Leaders retrieved successfully.');
    }

    public function getRoles() {
        $roles = Role::all();

        return $this->sendResponse($roles, 'Roles retrieved successfully.');
    }

    public function updateRole(Request $request, $id): JsonResponse
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = User::find($id);
        if (!$user) {
            return $this->sendError('User not found.', ['error' => 'User not found'], 404);
        }

        // Replace (not accumulate): the codebase reads the user's first role
        // everywhere, so stacking roles would leave stale leadership behavior.
        $user->syncRoles([$request->role]);

        return $this->sendResponse($user->fresh()->load('roles'), 'User role updated successfully.');
    }

    public function destroy($id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return $this->sendError('User not found.', ['error' => 'User not found'], 404);
        }

        if (auth()->id() === $user->id) {
            return $this->sendError('You cannot delete your own account.', [], 403);
        }

        // Soft delete: the row (and its role assignments) is kept, so
        // regions/bacentas led by this user keep referential integrity and
        // the account can be restored. Trashed users are excluded from
        // listings and cannot log in via the default Eloquent queries.
        $user->delete();

        return $this->sendResponse(null, 'User deleted successfully.');
    }

    public function updatePicture(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:8192',
        ]);

        try {
            $user = $request->user();

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

            return $this->sendResponse($user, 'Profile picture updated successfully.');
        } catch (Exception $e) {
            Log::error('Profile picture update error: ' . $e->getMessage());
            return $this->sendError('Failed to update profile picture.', ['error' => $e->getMessage()], 500);
        }
    }
}
