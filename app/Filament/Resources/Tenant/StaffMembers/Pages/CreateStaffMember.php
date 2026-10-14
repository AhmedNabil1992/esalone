<?php

namespace App\Filament\Resources\Tenant\StaffMembers\Pages;

use App\Filament\Resources\Tenant\StaffMembers\StaffMemberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStaffMember extends CreateRecord
{
    protected static string $resource = StaffMemberResource::class;
}
