<?php

namespace App\Http\Controllers\API\V2\Concerns;

use App\Models\Bacenta;
use App\Models\Region;

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
 * - Stream Lead / Region Lead / Zone Lead: rows stamped with their
 *   stream / region / zone, OR rows whose bacenta sits under it. The
 *   bacenta fallback covers rows whose denormalized columns are missing
 *   or stale (legacy rows, bacentas moved between regions), so they
 *   don't silently vanish from the lead's lists.
 * - Bacenta Leader: rows in any bacenta they lead.
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

        if ($scope = $this->hierarchyScope($user)) {
            [$column, $id, $bacentaIds] = $scope;
            return $query->where(function ($q) use ($column, $id, $bacentaIds) {
                $q->where($column, $id);
                if (!empty($bacentaIds)) {
                    $q->orWhereIn('bacenta_id', $bacentaIds);
                }
            });
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
     * The hierarchy level a Stream/Region/Zone Lead is scoped to, as
     * [column, id, ids of bacentas under it], or null when the lead has no
     * entity assigned. Checked in order: stream, region, zone.
     *
     * @return array{0: string, 1: int, 2: int[]}|null
     */
    protected function hierarchyScope($user): ?array
    {
        $toInts = fn ($ids) => $ids->map(fn ($id) => (int) $id)->all();

        if ($user->hasRole('Stream Lead') && $user->stream) {
            $streamId = (int) $user->stream->id;
            $regionIds = Region::where('stream_id', $streamId)->pluck('id');
            return ['stream_id', $streamId, $toInts(Bacenta::whereIn('region_id', $regionIds)->pluck('id'))];
        }

        if ($user->hasRole('Region Lead') && $user->region) {
            $regionId = (int) $user->region->id;
            return ['region_id', $regionId, $toInts(Bacenta::where('region_id', $regionId)->pluck('id'))];
        }

        if ($user->hasRole('Zone Lead') && $user->zone) {
            $zoneId = (int) $user->zone->id;
            return ['zone_id', $zoneId, $toInts(Bacenta::where('zone_id', $zoneId)->pluck('id'))];
        }

        return null;
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
        if ($user->hasRole(['Stream Lead', 'Region Lead', 'Zone Lead'])) {
            $scope = $this->hierarchyScope($user);
            if (!$scope) {
                return false;
            }
            [$column, $id, $bacentaIds] = $scope;
            if ((int) ($record->{$column} ?? 0) === $id) {
                return true;
            }
            return ($record->bacenta_id ?? null) !== null
                && in_array((int) $record->bacenta_id, $bacentaIds, true);
        }

        if ($user->hasRole('Bacenta Leader')) {
            return $record->bacenta_id !== null
                && in_array((int) $record->bacenta_id, $this->ledBacentaIds($user), true);
        }

        return false;
    }
}
