<?php

declare(strict_types=1);

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class BranchScope implements Scope
{
    /**
     * Request-lifecycle static cache for owner status.
     */
    private static ?bool $isOwnerCache = null;

    /**
     * Request-lifecycle static cache for assigned branch IDs.
     */
    private static ?array $assignedBranchIdsCache = null;

    /**
     * Reset static caches (useful for testing or user switching).
     */
    public static function resetCache(): void
    {
        static::$isOwnerCache = null;
        static::$assignedBranchIdsCache = null;
    }

    public function apply(Builder $builder, Model $model): void
    {
        if (! Auth::check()) {
            return;
        }

        $user = Auth::user();

        // 1. Memoized Role Check: Avoid repeated Shield/Spatie calls per query
        if (static::$isOwnerCache === null) {
            static::$isOwnerCache = method_exists($user, 'hasAnyRole')
                && $user->hasAnyRole(['owner', 'super_admin', 'Admin']);
        }

        $table = $model->getTable();

        // 2. Owner Flow: Global vs Branch Specific
        if (static::$isOwnerCache) {
            $activeBranchId = session('active_branch_id', 'all');

            if ($activeBranchId === 'all' || blank($activeBranchId)) {
                return; // Aggregated Global View across all branches
            }

            $builder->where("{$table}.branch_id", (int) $activeBranchId);

            return;
        }

        // 3. Memoized Branch Retrieval for Non-Owners
        if (static::$assignedBranchIdsCache === null) {
            if (method_exists($user, 'assignedBranchIds')) {
                static::$assignedBranchIdsCache = $user->assignedBranchIds();
            } elseif (method_exists($user, 'branches')) {
                static::$assignedBranchIdsCache = $user->branches()
                    ->pluck('branches.id')
                    ->map(fn ($id) => (int) $id)
                    ->toArray();
            } else {
                static::$assignedBranchIdsCache = [];
            }
        }

        $assignedBranchIds = static::$assignedBranchIdsCache;

        // 4. Fail-Safe Security: Non-Owner with NO assigned branches
        if (empty($assignedBranchIds)) {
            $builder->whereRaw('1 = 0');

            return;
        }

        // 5. Non-Owner Flow: Active Session Branch if assigned, or restrict to assigned branches
        $activeBranchId = session('active_branch_id');

        if ($activeBranchId && $activeBranchId !== 'all' && in_array((int) $activeBranchId, $assignedBranchIds, true)) {
            $builder->where("{$table}.branch_id", (int) $activeBranchId);
        } else {
            $builder->whereIn("{$table}.branch_id", $assignedBranchIds);
        }
    }
}
