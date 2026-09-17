<?php

namespace App\Filament\Resources\Tenant\Shifts\Shifts\Tables;

use App\Filament\Resources\Tenant\Shifts\Shifts\ShiftResource;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShiftsTable
{
    public static function configure(Table $table): Table
    {
        $defaultFrom = Carbon::now()->startOfMonth()->toDateString();
        $defaultUntil = Carbon::today()->toDateString();

        return $table
            ->columns([
                TextColumn::make('branch.name')
                    ->label('الفرع')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('shift_no')
                    ->label('رقم الشيفت')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('shift_date')
                    ->label('تاريخ الشيفت')
                    ->date()
                    ->sortable(),
                TextColumn::make('shift_from')
                    ->label('من')
                    ->dateTime('Y-m-d h:i A')
                    ->sortable(),
                TextColumn::make('shift_to')
                    ->label('إلى')
                    ->dateTime('Y-m-d h:i A')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('opener.name')
                    ->label('فتح بواسطة')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('closer.name')
                    ->label('إغلاق بواسطة')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('start_amount')
                    ->label('رصيد البداية')
                    ->money()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('إجمالي البداية')->money(),
                    ]),
                TextColumn::make('playstation_amount')
                    ->label('إيراد اللعب')
                    ->money()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->summarize([
                        Sum::make()->label('إجمالي اللعب')->money(),
                    ]),
                TextColumn::make('sales_amount')
                    ->label('إيراد المبيعات')
                    ->money()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->summarize([
                        Sum::make()->label('إجمالي المبيعات')->money(),
                    ]),
                TextColumn::make('expenses_amount')
                    ->label('المصروفات')
                    ->money()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->summarize([
                        Sum::make()->label('إجمالي المصروفات')->money(),
                    ]),
                TextColumn::make('purchase_amount')
                    ->label('المشتريات')
                    ->money()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->summarize([
                        Sum::make()->label('إجمالي المشتريات')->money(),
                    ]),
                TextColumn::make('discount_amount')
                    ->label('الخصومات')
                    ->money()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->summarize([
                        Sum::make()->label('إجمالي الخصومات')->money(),
                    ]),
                TextColumn::make('expected_amount')
                    ->label('الرصيد الدفتري')
                    ->money()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('إجمالي الدفتري')->money(),
                    ]),
                TextColumn::make('actual_amount')
                    ->label('الرصيد الفعلي')
                    ->money()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('إجمالي الفعلي')->money(),
                        Count::make()->label('عدد الشيفتات'),
                    ]),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'success',
                        'close' => 'gray',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'مفتوح',
                        'close' => 'مغلق',
                        default => $state,
                    }),
                ToggleColumn::make('is_active')
                    ->label('نشط'),
            ])
            ->defaultSort('shift_no', 'desc')
            ->filters([
                Filter::make('shift_date')
                    ->label('تاريخ الشيفت')
                    ->form([
                        DatePicker::make('from')
                            ->label('من تاريخ')
                            ->default($defaultFrom),
                        DatePicker::make('until')
                            ->label('إلى تاريخ')
                            ->default($defaultUntil),
                    ])
                    ->query(function (Builder $query, array $data) use ($defaultFrom, $defaultUntil): Builder {
                        $from = $data['from'] ?? $defaultFrom;
                        $until = $data['until'] ?? $defaultUntil;

                        return $query
                            ->when(
                                filled($from),
                                fn (Builder $builder): Builder => $builder->whereDate('shift_date', '>=', $from)
                            )
                            ->when(
                                filled($until),
                                fn (Builder $builder): Builder => $builder->whereDate('shift_date', '<=', $until)
                            );
                    }),
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'open' => 'مفتوح',
                        'close' => 'مغلق',
                    ]),
                SelectFilter::make('opened_by_user_id')
                    ->label('فتح بواسطة')
                    ->relationship('opener', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('closed_by_user_id')
                    ->label('إغلاق بواسطة')
                    ->relationship('closer', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->pluralModelLabel('الصفحات')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('close')
                    ->label('تقفيل الوردية')
                    ->color('danger')
                    ->icon('heroicon-o-lock-closed')
                    ->url(fn ($record) => ShiftResource::getUrl('close', ['record' => $record]))
                    ->visible(fn ($record) => $record->status === 'open' && $record->is_active),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(fn ($record) => ShiftResource::getUrl('view', ['record' => $record]));
    }
}
