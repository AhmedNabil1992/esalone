<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Tenant\DiscountHistory;
use App\Support\Tenant\TenantClock;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
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

class DiscountHistoryReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static ?string $title = 'سجل الخصومات';

    protected static ?string $navigationLabel = 'سجل الخصومات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.discount-history-report';

    public function table(Table $table): Table
    {
        $clock = app(TenantClock::class);
        $defaultFrom = $clock->nowLocal()->startOfMonth()->toDateString();
        $defaultUntil = $clock->nowLocal()->toDateString();

        return $table
            ->query(DiscountHistory::query()->with(['branch', 'shift', 'user', 'invoice'])->latest('id'))
            ->columns([
                TextColumn::make('branch.name')
                    ->label('الفرع')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('invoice_no')
                    ->label('رقم الفاتورة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money()
                    ->placeholder('-')
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('إجمالي الخصومات')->money(),
                        Count::make()->label('عدد السجلات'),
                    ]),
                TextColumn::make('reason')
                    ->label('السبب')
                    ->placeholder('-'),
                TextColumn::make('shift_id')
                    ->label('شيفت رقم')
                    ->formatStateUsing(fn ($state, $record) => $record->shift ? "#{$record->shift->shift_no}" : ($state ? "#{$state}" : '-'))
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('occurred_at')
                    ->label('تاريخ الخصم')
                    ->dateTime('Y-m-d h:i A')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d h:i A')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime('Y-m-d h:i A')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('occurred_at')
                    ->label('تاريخ الخصم')
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
                                fn (Builder $builder): Builder => $builder->where('occurred_at', '>=', app(TenantClock::class)->localDateStartUtc($from))
                            )
                            ->when(
                                filled($until),
                                fn (Builder $builder): Builder => $builder->where('occurred_at', '<=', app(TenantClock::class)->localDateEndUtc($until))
                            );
                    }),
                SelectFilter::make('shift_id')
                    ->label('شيفت رقم')
                    ->options(fn (): array => DiscountHistory::query()
                        ->whereNotNull('shift_id')
                        ->orderByDesc('shift_id')
                        ->distinct()
                        ->pluck('shift_id', 'shift_id')
                        ->toArray())
                    ->searchable(),
                SelectFilter::make('user_id')
                    ->label('المستخدم')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->pluralModelLabel('الصفحات')
            ->defaultSort('id', 'desc');
    }
}
