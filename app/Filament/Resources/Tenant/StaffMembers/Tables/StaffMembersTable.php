<?php

namespace App\Filament\Resources\Tenant\StaffMembers\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StaffMembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('job_title')
                    ->label('التخصص')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->placeholder('—'),
                TextColumn::make('commission_rate')
                    ->label('العمولة')
                    ->suffix('%')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('يستقبل الحجوزات')
                    ->boolean(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('name');
    }
}
