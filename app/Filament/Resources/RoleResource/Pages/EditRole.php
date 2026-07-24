<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\EditRole as BaseEditRole;

class EditRole extends BaseEditRole
{
    protected static string $resource = RoleResource::class;
}
