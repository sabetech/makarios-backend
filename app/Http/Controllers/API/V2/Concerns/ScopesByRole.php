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

        if ($user->hasRole('Bacenta Leader') && $user->bacenta) {
            return $query->where('bacenta_id', $user->bacenta->id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Check a single hierarchy-stamped record against the user's scope.
     */
    protected function recordInScope($record, $user): bool
    {
        if ($user->hasRole(['Super Admin', 'General Admin', 'Bishop'])) {
            return true;
        }

        if ($user->hasRole('Stream Lead')) {
            return (bool) ($user->stream && $record->stream_id === $user->stream->id);
        }

        if ($user->hasRole('Region Lead')) {
            return (bool) ($user->region && $record->region_id === $user->region->id);
        }

        if ($user->hasRole('Zone Lead')) {
            return (bool) ($user->zone && $record->zone_id === $user->zone->id);
        }

        if ($user->hasRole('Bacenta Leader')) {
            return (bool) ($user->bacenta && $record->bacenta_id === $user->bacenta->id);
        }

        return false;
    }
}
