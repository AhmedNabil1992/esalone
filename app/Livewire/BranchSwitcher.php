<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Tenant\Branch;
use App\Scopes\BranchScope;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BranchSwitcher extends Component
{
    public string|int $activeBranchId = 'all';

    public bool $isOwner = false;

    /**
     * @var Collection<int, Branch>
     */
    public Collection $branches;

    public function mount(): void
    {
        if (! Auth::check()) {
            $this->branches = collect();

            return;
        }

        $user = Auth::user();

        $this->isOwner = method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['owner', 'super_admin', 'Admin']);

        if ($this->isOwner) {
            $this->branches = Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            $this->branches = $user->branches()
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        $sessionBranchId = session('active_branch_id');

        if ($this->branches->count() === 1) {
            // Exactly one branch: ALWAYS select that branch (never 'all')
            $defaultBranchId = $this->branches->first()->id;
            $this->activeBranchId = $defaultBranchId;
            session(['active_branch_id' => $defaultBranchId]);
        } elseif ($sessionBranchId !== null && ($sessionBranchId === 'all' || $this->branches->contains('id', (int) $sessionBranchId))) {
            // User had manually selected a branch or 'all' in session
            $this->activeBranchId = $sessionBranchId;
        } else {
            // Default on first login/session for multi-branch: select first branch
            $defaultBranchId = $this->branches->first()?->id ?? 'all';
            $this->activeBranchId = $defaultBranchId;
            session(['active_branch_id' => $defaultBranchId]);
        }
    }

    public function switchBranch(string|int $branchId): void
    {
        if (! Auth::check()) {
            return;
        }

        // When there is only 1 branch, 'all' is not allowed
        if ($this->branches->count() <= 1 && $branchId === 'all') {
            return;
        }

        // Security check: Only owners can select 'all'
        if ($branchId === 'all' && ! $this->isOwner) {
            return;
        }

        // Security check: Non-owners can only select assigned branches
        if ($branchId !== 'all' && ! $this->branches->contains('id', (int) $branchId)) {
            return;
        }

        $normalizedBranchId = is_numeric($branchId) ? (int) $branchId : $branchId;
        session(['active_branch_id' => $normalizedBranchId]);
        session()->save();
        $this->activeBranchId = $normalizedBranchId;

        BranchScope::resetCache();

        $this->redirect(request()->header('Referer', route('filament.admin.pages.dashboard')), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.branch-switcher');
    }
}
