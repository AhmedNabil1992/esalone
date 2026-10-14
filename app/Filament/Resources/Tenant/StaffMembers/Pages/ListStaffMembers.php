<?php

namespace App\Filament\Resources\Tenant\StaffMembers\Pages;

use App\Filament\Resources\Tenant\StaffMembers\StaffMemberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStaffMembers extends ListRecords
{
    protected static string $resource = StaffMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
