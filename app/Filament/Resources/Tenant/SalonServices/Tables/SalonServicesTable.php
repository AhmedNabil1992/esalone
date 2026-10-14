<?php

namespace App\Filament\Resources\Tenant\SalonServices\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalonServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الخدمة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label('التصنيف')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'hair' => 'الشعر',
                        'barber' => 'الحلاقة',
                        'skin' => 'العناية بالبشرة',
                        'nails' => 'العناية بالأظافر',
                        'makeup' => 'المكياج',
                        'other' => 'أخرى',
                        default => '—',
                    })
                    ->badge(),
                TextColumn::make('duration_minutes')
                    ->label('المدة')
                    ->suffix(' دقيقة')
                    ->sortable(),
                TextColumn::make('price')
                    ->label('السعر')
                    ->money('EGP')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('متاحة')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('التصنيف')
                    ->options([
                        'hair' => 'الشعر',
                        'barber' => 'الحلاقة',
                        'skin' => 'العناية بالبشرة',
                        'nails' => 'العناية بالأظافر',
                        'makeup' => 'المكياج',
                        'other' => 'أخرى',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('name');
    }
}
