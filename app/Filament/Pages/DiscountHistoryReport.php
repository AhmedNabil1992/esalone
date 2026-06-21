<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Tenant\DiscountHistory;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DiscountHistoryReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'تقرير سجل الخصومات';

    protected static ?string $navigationLabel = 'تقرير سجل الخصومات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.discount-history-report';

    public function table(Table $table): Table
    {
        $defaultFrom = Carbon::now()->startOfMonth()->toDateString();
        $defaultUntil = Carbon::today()->toDateString();

        return $table
            ->query(DiscountHistory::query()->latest('id'))
            ->columns([
                TextColumn::make('invoice_no')
                    ->label('رقم الفاتورة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money()
                    ->placeholder('-'),
                TextColumn::make('reason')
                    ->label('السبب')
                    ->placeholder('-'),
                TextColumn::make('shift_id')
                    ->label('شيفت رقم')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('occurred_at')
                    ->label('تاريخ الخصم')
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
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
                                fn (Builder $builder): Builder => $builder->whereDate('occurred_at', '>=', $from)
                            )
                            ->when(
                                filled($until),
                                fn (Builder $builder): Builder => $builder->whereDate('occurred_at', '<=', $until)
                            );
                    }),
            ]);
    }
}
