<?php

namespace App\Filament\Pages;

use App\Models\Tenant\PlayHistory;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PlayTypesReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static ?string $title = 'تقرير تحليل أنواع اللعب والأجهزة';

    protected static ?string $navigationLabel = 'تقرير تحليل أنواع اللعب والأجهزة';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string|\UnitEnum|null $navigationGroup = 'تقارير إدارية';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.play-types-report';

    public function getTableRecordKey(Model|array $record): string
    {
        if (is_array($record)) {
            return ($record['device_id'] ?? '0').'-'.($record['play_type'] ?? 'unknown');
        }

        return ($record->device_id ?? '0').'-'.($record->play_type ?? 'unknown');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PlayHistoryGroupedReport::query()
                    ->join('devices', 'play_history.device_id', '=', 'devices.id')
                    ->select(
                        'play_history.device_id',
                        'devices.name as device_name',
                        'play_history.play_type',
                        DB::raw('COUNT(*) as sessions_count'),
                        DB::raw('SUM(play_history.play_time) as total_play_time'),
                        DB::raw('SUM(play_history.cost) as total_revenue')
                    )
                    ->groupBy('play_history.device_id', 'devices.name', 'play_history.play_type')
            )
            ->columns([
                TextColumn::make('device_name')
                    ->label('اسم الجهاز')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('play_type')
                    ->label('نوع اللعب')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('sessions_count')
                    ->label('عدد الجلسات')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('total_play_time')
                    ->label('إجمالي الوقت ملعوب')
                    ->alignCenter()
                    ->formatStateUsing(function ($state): string {
                        $minutes = (int) $state;
                        $hours = floor($minutes / 60);
                        $remainingMin = $minutes % 60;

                        return "{$hours} س و {$remainingMin} د";
                    })
                    ->sortable(),

                TextColumn::make('total_revenue')
                    ->label('إيراد اللعب')
                    ->money('EGP')
                    ->sortable()
                    ->summarize(
                        Summarizer::make()
                            ->label('إجمالي إيراد اللعب')
                            ->money('EGP')
                            ->using(fn ($query): float => (float) $query->clone()->sum('total_revenue'))
                    ),
            ])
            ->defaultSort('device_name')
            ->paginated(false)
            ->filters([
                Filter::make('trx_date')
                    ->form([
                        DatePicker::make('from')
                            ->label('من تاريخ')
                            ->default(Carbon::now()->startOfMonth()),
                        DatePicker::make('until')
                            ->label('إلى تاريخ')
                            ->default(Carbon::today()),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q) => $q->whereDate('trx_date', '>=', $data['from']))
                            ->when($data['until'] ?? null, fn ($q) => $q->whereDate('trx_date', '<=', $data['until']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'من تاريخ: '.Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'إلى تاريخ: '.Carbon::parse($data['until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ], layout: FiltersLayout::AboveContent);
    }
}

class PlayHistoryGroupedReport extends PlayHistory
{
    protected $table = 'play_history';

    public $incrementing = false;

    protected $keyType = 'string';

    public function getKeyName(): string
    {
        return 'device_name';
    }
}
