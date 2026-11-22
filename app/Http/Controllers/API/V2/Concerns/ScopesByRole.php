<?php

namespace App\Http\Controllers\API\V2\Concerns;

/**
 * Shared role scoping for hierarchy-aware reads.
 *
 * Members, services and similar rows carry denormalized
 * stream_id / region_id / zone_id / bacenta_id columns. Every
 * role-scoped listing and every single-record access check must use
 * this helper so the rules cannot drift apart between controllers.
 *
 * Visibility rules:
 * - Super Admin / General Admin / Bishop: everything (including rows
 *   with NULL hierarchy columns, e.g. bacenta-less members).
 * - Stream Lead / Region Lead / Zone Lead / Bacenta Leader: only rows
 *   stamped with their own stream / region / zone / bacenta.
 * - Anyone else: nothing.
 */
trait ScopesByRole
{
    /**
     * Constrain a query to the rows the user may see.
     */
    protected function applyRoleScope($query, $user)
    {
        if ($user->hasRole(['Super Admin', 'General Admin', 'Bishop'])) {
            return $query;
        }

        if ($user->hasRole('Stream Lead') && $user->stream) {
            return $query->where('stream_id', $user->stream->id);
        }

        if ($user->hasRole('Region Lead') && $user->region) {
            return $query->where('region_id', $user->region->id);
        }

        if ($user->hasRole('Zone Lead') && $user->zone) {
            return $query->where('zone_id', $user->zone->id);
        }

        if ($user->hasRole('Bacenta Leader')) {
            $bacentaIds = $this->ledBacentaIds($user);
            if (!empty($bacentaIds)) {
                return $query->whereIn('bacenta_id', $bacentaIds);
            }
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Ids of every bacenta the user leads (a leader can lead several).
     *
     * @return int[]
     */
    protected function ledBacentaIds($user): array
    {
        return $user->ledBacentas()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Check a single hierarchy-stamped record against the user's scope.
     */
    protected function recordInScope($record, $user): bool
    {
        if ($user->hasRole(['Super Admin', 'General Admin', 'Bishop'])) {
            return true;
        }

        // Ids are cast to int: DB drivers may hydrate them as strings, and
        // request payloads may carry either form.
        if ($user->hasRole('Stream Lead')) {
            return (bool) ($user->stream && (int) $record->stream_id === (int) $user->stream->id);
        }

        if ($user->hasRole('Region Lead')) {
            return (bool) ($user->region && (int) $record->region_id === (int) $user->region->id);
        }

        if ($user->hasRole('Zone Lead')) {
            return (bool) ($user->zone && (int) $record->zone_id === (int) $user->zone->id);
        }

        if ($user->hasRole('Bacenta Leader')) {
            return $record->bacenta_id !== null
                && in_array((int) $record->bacenta_id, $this->ledBacentaIds($user), true);
        }

        return false;
    }
}
