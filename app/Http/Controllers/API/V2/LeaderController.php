<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Bacenta;
use App\Models\Region;
use App\Models\Stream;
use App\Models\User;
use App\Models\Zone;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Admin management of a single leader: profile, role, and the
 * streams/regions/zones/bacentas they lead.
 */
class LeaderController extends BaseController
{
    /**
     * Entity types a user can lead: the model, the column on that model
     * pointing at the leader, and the role that goes with leading it.
     * A user's role and what they lead are kept in step: changing or
     * removing the role clears the entities that belonged to the old role.
     */
    private const ENTITY_TYPES = [
        'stream' => ['model' => Stream::class, 'column' => 'stream_overseer_id', 'role' => 'Stream Lead', 'single' => false],
        'region' => ['model' => Region::class, 'column' => 'leader_id', 'role' => 'Region Lead', 'single' => true],
        'zone' => ['model' => Zone::class, 'column' => 'leader_id', 'role' => 'Zone Lead', 'single' => true],
        'bacenta' => ['model' => Bacenta::class, 'column' => 'leader_id', 'role' => 'Bacenta Leader', 'single' => false],
    ];

    public function show($id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return $this->sendError('Leader not found.', [], 404);
        }

        return $this->sendResponse($this->profile($user), 'Leader retrieved successfully.');
    }

    /**
     * Update name and/or role. Sending `role: null` removes the role.
     * Changing or removing the role unassigns the leader from every entity
     * that belonged to their previous role.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'role' => 'sometimes|nullable|string|exists:roles,name',
        ]);

        $user = User::find($id);
        if (!$user) {
            return $this->sendError('Leader not found.', [], 404);
        }

        $changingRole = $request->exists('role');
        $newRole = $request->input('role');
        $oldRoles = $user->getRoleNames()->all();

        if ($changingRole && $oldRoles !== array_filter([$newRole])) {
            if ((int) Auth::id() === (int) $user->id) {
                return $this->sendError('You cannot change your own role.', [], 403);
            }
        } else {
            $changingRole = false;
        }

        $unassigned = DB::transaction(function () use ($request, $user, $changingRole, $newRole, $oldRoles) {
            if ($request->has('name')) {
                $user->update(['name' => $request->input('name')]);
            }

            $unassigned = [];
            if ($changingRole) {
                foreach (self::ENTITY_TYPES as $type => $config) {
                    if (in_array($config['role'], $oldRoles, true) && $config['role'] !== $newRole) {
                        $count = $config['model']::where($config['column'], $user->id)
                            ->update([$config['column'] => null]);
                        if ($count > 0) {
                            $unassigned[$type] = $count;
                        }
                    }
                }

                // Replace rather than stack: the codebase reads a user's first role.
                $user->syncRoles($newRole ? [$newRole] : []);
            }

            return $unassigned;
        });

        return $this->sendResponse(
            $this->profile($user->fresh()) + ['unassigned' => (object) $unassigned],
            'Leader updated successfully.'
        );
    }

    public function updatePicture(Request $request, $id): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:8192',
        ]);

        $user = User::find($id);
        if (!$user) {
            return $this->sendError('Leader not found.', [], 404);
        }

        try {
            $file = $request->file('image');
            $result = Cloudinary::upload($file->getRealPath(), [
                'folder' => 'app-users',
                'public_id' => 'user_' . $user->id . '_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'overwrite' => true,
                'resource_type' => 'image',
            ]);
            $user->update(['img_url' => $result->getSecurePath()]);
        } catch (Exception $e) {
            Log::error('Leader picture update error: ' . $e->getMessage());
            return $this->sendError('Failed to update profile picture.', ['error' => $e->getMessage()], 500);
        }

        return $this->sendResponse($this->profile($user->fresh()), 'Profile picture updated successfully.');
    }

    /**
     * Entities of a type that a leader could be assigned to, with whoever
     * leads each one now.
     */
    public function assignable($type): JsonResponse
    {
        $config = self::ENTITY_TYPES[$type] ?? null;
        if (!$config) {
            return $this->sendError('Unknown entity type.', [], 422);
        }

        $entities = $config['model']::orderBy('name')->get();
        $leaders = User::whereIn('id', $entities->pluck($config['column'])->filter()->unique())
            ->get(['id', 'name'])
            ->keyBy('id');

        $entities = $entities->map(function (Model $entity) use ($type, $config, $leaders) {
            $leader = $leaders->get($entity->{$config['column']});
            return $this->entitySummary($type, $entity) + [
                'leader' => $leader ? ['id' => $leader->id, 'name' => $leader->name] : null,
            ];
        });

        return $this->sendResponse($entities, 'Assignable entities retrieved successfully.');
    }

    /**
     * Assign the leader to an entity, or move them from `from_id` to
     * `entity_id` (same type). Whoever led the target before is removed
     * from it and loses the matching role unless they still lead another
     * entity of that type.
     */
    public function assign(Request $request, $id): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:' . implode(',', array_keys(self::ENTITY_TYPES)),
            'entity_id' => 'required|integer',
            'from_id' => 'nullable|integer',
        ]);

        $user = User::find($id);
        if (!$user) {
            return $this->sendError('Leader not found.', [], 404);
        }

        $type = $request->input('type');
        $config = self::ENTITY_TYPES[$type];
        $model = $config['model'];
        $column = $config['column'];

        if (!$user->hasRole($config['role'])) {
            return $this->sendError("Give this leader the {$config['role']} role first.", [], 422);
        }

        $target = $model::find($request->input('entity_id'));
        if (!$target) {
            return $this->sendError(ucfirst($type) . ' not found.', [], 404);
        }
        if ((int) $target->{$column} === (int) $user->id) {
            return $this->sendError("This leader already leads {$target->name}.", [], 422);
        }

        $from = null;
        if ($request->filled('from_id')) {
            $from = $model::find($request->input('from_id'));
            if (!$from || (int) $from->{$column} !== (int) $user->id) {
                return $this->sendError("This leader doesn't lead that {$type}.", [], 422);
            }
        } elseif ($config['single'] && $model::where($column, $user->id)->exists()) {
            // Access checks resolve a Region/Zone Lead's entity via a single hasOne.
            return $this->sendError("This leader already leads a {$type}. Move them instead.", [], 422);
        }

        $displacedId = $target->{$column} ? (int) $target->{$column} : null;

        DB::transaction(function () use ($user, $target, $from, $model, $column, $config, $displacedId) {
            if ($from) {
                $from->update([$column => null]);
            }
            $target->update([$column => $user->id]);

            if ($displacedId) {
                $displaced = User::find($displacedId);
                $stillLeads = $model::where($column, $displacedId)->exists();
                if ($displaced && !$stillLeads && $displaced->hasRole($config['role'])) {
                    $displaced->removeRole($config['role']);
                }
            }
        });

        return $this->sendResponse($this->profile($user->fresh()), 'Leader assigned successfully.');
    }

    /**
     * Remove the leader from an entity. Their role is kept; the entity is
     * left without a leader.
     */
    public function unassign($id, $type, $entityId): JsonResponse
    {
        $config = self::ENTITY_TYPES[$type] ?? null;
        if (!$config) {
            return $this->sendError('Unknown entity type.', [], 422);
        }

        $user = User::find($id);
        if (!$user) {
            return $this->sendError('Leader not found.', [], 404);
        }

        $entity = $config['model']::find($entityId);
        if (!$entity || (int) $entity->{$config['column']} !== (int) $user->id) {
            return $this->sendError("This leader doesn't lead that {$type}.", [], 422);
        }

        $entity->update([$config['column'] => null]);

        return $this->sendResponse($this->profile($user->fresh()), 'Leader unassigned successfully.');
    }

    private function profile(User $user): array
    {
        $role = $user->getRoleNames()->first();
        $roleType = collect(self::ENTITY_TYPES)->search(fn ($config) => $config['role'] === $role) ?: null;

        $leading = [];
        foreach (self::ENTITY_TYPES as $type => $config) {
            $config['model']::where($config['column'], $user->id)->orderBy('name')->get()
                ->each(function (Model $entity) use ($type, &$leading) {
                    $leading[] = $this->entitySummary($type, $entity);
                });
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'img_url' => $user->img_url,
            'role' => $role,
            'role_entity_type' => $roleType,
            'leading' => $leading,
        ];
    }

    private function entitySummary(string $type, Model $entity): array
    {
        $context = match ($type) {
            'region' => $entity->stream?->name,
            'zone', 'bacenta' => $entity->region?->name,
            default => null,
        };

        return [
            'type' => $type,
            'id' => $entity->id,
            'name' => $entity->name,
            'context' => $context,
        ];
    }
}
