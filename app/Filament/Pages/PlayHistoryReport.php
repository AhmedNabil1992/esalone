<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Tenant\PlayHistory;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlayHistoryReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static ?string $title = 'سجل اللعب';

    protected static ?string $navigationLabel = 'سجل اللعب';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.play-history-report';

    public function table(Table $table): Table
    {
        $defaultFrom = Carbon::now()->startOfMonth()->toDateString();
        $defaultUntil = Carbon::today()->toDateString();

        return $table
            ->query(PlayHistory::query()->with(['device.type'])->latest('id'))
            ->columns([
                TextColumn::make('trx_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                TextColumn::make('invoice_no')
                    ->label('رقم الفاتورة')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('device.name')
                    ->label('اسم الجهاز')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('device.type.name')
                    ->label('نوع الجهاز')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('play_type')
                    ->label('نوع اللعب')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('hour_price')
                    ->label('سعر الساعة')
                    ->money()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('play_time')
                    ->label('المدة (دقيقة)')
                    ->placeholder('-')
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('إجمالي الدقائق'),
                    ]),
                TextColumn::make('cost')
                    ->label('الإيراد')
                    ->money()
                    ->sortable()
                    ->placeholder('-')
                    ->summarize([
                        Sum::make()->label('إجمالي الإيراد')->money(),
                        Count::make()->label('عدد السجلات'),
                    ]),
                TextColumn::make('start_time')
                    ->label('بداية الوقت')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('end_time')
                    ->label('نهاية الوقت')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('actual_end_time')
                    ->label('إقفال فعلي')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('username')
                    ->label('بواسطة')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('shift.shift_no')
                    ->label('رقم الشيفت')
                    ->formatStateUsing(fn ($state, $record) => $state ? "#{$state}" : ($record->shift_id ? "#{$record->shift_id}" : '-'))
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                Filter::make('trx_date')
                    ->label('تاريخ اللعب')
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
                                fn (Builder $builder): Builder => $builder->whereDate('trx_date', '>=', $from)
                            )
                            ->when(
                                filled($until),
                                fn (Builder $builder): Builder => $builder->whereDate('trx_date', '<=', $until)
                            );
                    }),
                SelectFilter::make('device_type_id')
                    ->label('نوع الجهاز')
                    ->options(fn (): array => $this->getDeviceTypeFilterOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return $query;
                        }

                        return $query->whereHas(
                            'device',
                            fn (Builder $deviceQuery): Builder => $deviceQuery->where('devices_type_id', $value)
                        );
                    })
                    ->searchable()
                    ->preload(),
                SelectFilter::make('device_id')
                    ->label('اسم الجهاز')
                    ->options(fn (): array => $this->getDeviceFilterOptions())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('play_type')
                    ->label('نوع اللعب')
                    ->options(fn (): array => $this->getPlayTypeFilterOptions())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('username')
                    ->label('بواسطة (الكاشير)')
                    ->options(fn (): array => $this->getUsernameFilterOptions())
                    ->searchable(),
                SelectFilter::make('shift_id')
                    ->label('الشيفت')
                    ->options(fn (): array => $this->getShiftFilterOptions())
                    ->searchable(),

            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->pluralModelLabel('الصفحات')
            ->defaultSort('id', 'desc');
    }

    private function getBaseFilteredQuery(?string $excludeFilter = null): Builder
    {
        $defaultFrom = now()->startOfDay()->toDateString();
        $defaultUntil = now()->endOfDay()->toDateString();

        $from = data_get($this->getTableFilterState('trx_date'), 'from', $defaultFrom);
        $until = data_get($this->getTableFilterState('trx_date'), 'until', $defaultUntil);
        $deviceTypeId = data_get($this->getTableFilterState('device_type_id'), 'value');
        $deviceId = data_get($this->getTableFilterState('device_id'), 'value');
        $playType = data_get($this->getTableFilterState('play_type'), 'value');
        $username = data_get($this->getTableFilterState('username'), 'value');
        $shiftId = data_get($this->getTableFilterState('shift_id'), 'value');

        return PlayHistory::query()
            ->when(
                filled($from),
                fn (Builder $query): Builder => $query->whereDate('trx_date', '>=', $from)
            )
            ->when(
                filled($until),
                fn (Builder $query): Builder => $query->whereDate('trx_date', '<=', $until)
            )
            ->when(
                $excludeFilter !== 'device_type_id' && filled($deviceTypeId),
                fn (Builder $query): Builder => $query->whereHas(
                    'device',
                    fn (Builder $deviceQuery): Builder => $deviceQuery->where('devices_type_id', $deviceTypeId)
                )
            )
            ->when(
                $excludeFilter !== 'device_id' && filled($deviceId),
                fn (Builder $query): Builder => $query->where('device_id', $deviceId)
            )
            ->when(
                $excludeFilter !== 'play_type' && filled($playType),
                fn (Builder $query): Builder => $query->where('play_type', $playType)
            )
            ->when(
                $excludeFilter !== 'username' && filled($username),
                fn (Builder $query): Builder => $query->where('username', $username)
            )
            ->when(
                $excludeFilter !== 'shift_id' && filled($shiftId),
                fn (Builder $query): Builder => $query->where('shift_id', $shiftId)
            );
    }

    /**
     * @return array<string, string>
     */
    private function getDeviceTypeFilterOptions(): array
    {
        $options = [];

        foreach ($this->getBaseFilteredQuery(excludeFilter: 'device_type_id')->with(['device.type'])->get() as $playHistory) {
            $deviceTypeId = $playHistory->device?->type?->id;
            $deviceTypeName = trim((string) ($playHistory->device?->type?->name ?? ''));

            if (blank($deviceTypeId) || $deviceTypeName === '') {
                continue;
            }

            $options[(string) $deviceTypeId] = $deviceTypeName;
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private function getDeviceFilterOptions(): array
    {
        $options = [];

        foreach ($this->getBaseFilteredQuery(excludeFilter: 'device_id')->with(['device'])->get() as $playHistory) {
            $deviceId = $playHistory->device?->id;
            $deviceName = trim((string) ($playHistory->device?->name ?? ''));

            if (blank($deviceId) || $deviceName === '') {
                continue;
            }

            $options[(string) $deviceId] = $deviceName;
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private function getPlayTypeFilterOptions(): array
    {
        return $this->getBaseFilteredQuery(excludeFilter: 'play_type')
            ->whereNotNull('play_type')
            ->where('play_type', '!=', '')
            ->orderBy('play_type')
            ->distinct()
            ->pluck('play_type', 'play_type')
            ->toArray();
    }

    /**
     * @return array<string, string>
     */
    private function getUsernameFilterOptions(): array
    {
        return $this->getBaseFilteredQuery(excludeFilter: 'username')
            ->whereNotNull('username')
            ->where('username', '!=', '')
            ->orderBy('username')
            ->distinct()
            ->pluck('username', 'username')
            ->toArray();
    }

    /**
     * @return array<string, string>
     */
    private function getShiftFilterOptions(): array
    {
        return $this->getBaseFilteredQuery(excludeFilter: 'shift_id')
            ->whereNotNull('shift_id')
            ->orderBy('shift_id', 'desc')
            ->with(['shift'])
            ->get()
            ->mapWithKeys(function ($history) {
                $shiftNo = $history->shift?->shift_no ?? $history->shift_id;

                return [(string) $history->shift_id => "الشيفت #{$shiftNo}"];
            })
            ->toArray();
    }
}
