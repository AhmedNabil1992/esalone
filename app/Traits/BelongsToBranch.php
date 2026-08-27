<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Tenant\Branch;
use App\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::addGlobalScope(new BranchScope);

        static::creating(function ($model): void {
            if (! $model->branch_id) {
                $activeBranchId = session('active_branch_id');

                if ($activeBranchId && $activeBranchId !== 'all') {
                    $model->branch_id = (int) $activeBranchId;
                } elseif (Auth::check()) {
                    $user = Auth::user();
                    if (method_exists($user, 'assignedBranchIds')) {
                        $assignedIds = $user->assignedBranchIds();
                        if (! empty($assignedIds)) {
                            $model->branch_id = $assignedIds[0];
                        }
                    }
                }
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
