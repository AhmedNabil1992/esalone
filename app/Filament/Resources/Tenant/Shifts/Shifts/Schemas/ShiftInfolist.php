<?php

namespace App\Filament\Resources\Tenant\Shifts\Shifts\Schemas;

use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ShiftInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.infolists.shift-dashboard'),
            ]);
    }
}
