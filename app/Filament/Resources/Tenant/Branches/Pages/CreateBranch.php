<?php

namespace App\Filament\Resources\Tenant\Branches\Pages;

use App\Filament\Resources\Tenant\Branches\BranchResource;
use App\Models\Tenant\Branch;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateBranch extends CreateRecord
{
    protected static string $resource = BranchResource::class;

    protected function beforeCreate(): void
    {
        $tenant = tenancy()->tenant;
        if ($tenant && $tenant->plan_id) {
            $limit = DB::table('plan_features')
                ->where('plan_id', $tenant->plan_id)
                ->where('feature', 'branches')
                ->value('limit_value');

            if ($limit !== null && Branch::count() >= (int) $limit) {
                Notification::make()
                    ->title('عفواً، لقد وصلت للحد الأقصى المسموح به من الفروع للباقة الحالية')
                    ->body("الحد الأقصى المسموح به في باقتك الحالية هو {$limit} فرع. يرجى ترقية الباقة لإضافة فروع جديدة.")
                    ->danger()
                    ->persistent()
                    ->send();

                $this->halt();
            }
        }
    }
}
