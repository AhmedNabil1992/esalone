<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Tenant\PlayHistory;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlayHistoryReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'تقرير سجل اللعب';

    protected static ?string $navigationLabel = 'تقرير سجل اللعب';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.play-history-report';

    public function table(Table $table): Table
    {
        $defaultFrom = Carbon::now()->startOfMonth()->toDateString();
        $defaultUntil = Carbon::today()->toDateString();

        return $table
            ->query(PlayHistory::query()->with(['device.type'])->latest('id'))
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('trx_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                TextColumn::make('invoice_no')
                    ->label('رقم الفاتورة')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('device_id')
                    ->label('ID الجهاز')
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
                    ->sortable(),
                TextColumn::make('cost')
                    ->label('الإيراد')
                    ->money()
                    ->sortable()
                    ->placeholder('-'),
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
                TextColumn::make('shift_no')
                    ->label('الشيفت')
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
            ])
            ->defaultSort('id', 'desc');
    }
}
