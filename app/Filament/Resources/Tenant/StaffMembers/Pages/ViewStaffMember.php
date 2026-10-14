<?php

namespace App\Filament\Resources\Tenant\StaffMembers\Pages;

use App\Filament\Resources\Tenant\StaffMembers\StaffMemberResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStaffMember extends ViewRecord
{
    protected static string $resource = StaffMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
