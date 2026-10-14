<?php

namespace App\Filament\Pages;

use App\Models\Tenant\SalonService;
use App\Support\Tenant\TenantClock;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServicePerformanceReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static ?string $title = 'تقرير أداء الخدمات';

    protected static ?string $navigationLabel = 'أداء الخدمات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-scissors';

    protected static string|\UnitEnum|null $navigationGroup = 'تقارير إدارية';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.service-performance-report';

    public function table(Table $table): Table
    {
        $clock = app(TenantClock::class);
        $filterData = $this->tableFilters['invoice_period'] ?? [];
        $from = $filterData['from'] ?? $clock->nowLocal()->startOfMonth()->toDateString();
        $until = $filterData['until'] ?? $clock->nowLocal()->toDateString();

        return $table
            ->query($this->getServicePerformanceQuery($from, $until))
            ->columns([
                TextColumn::make('name')
                    ->label('الخدمة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('category')
                    ->label('التصنيف')
                    ->placeholder('بدون تصنيف')
                    ->badge(),
                TextColumn::make('completed_services_count')
                    ->label('مرات التقديم والتحصيل')
                    ->numeric(decimalPlaces: 0)
                    ->sortable()
                    ->summarize(Sum::make()->label('إجمالي مرات التقديم')),
                TextColumn::make('recorded_service_value')
                    ->label('قيمة الخدمات المسجلة')
                    ->money('EGP')
                    ->sortable()
                    ->summarize(Sum::make()->label('إجمالي القيمة المسجلة')),
                TextColumn::make('duration_minutes')
                    ->label('المدة المعتادة')
                    ->formatStateUsing(fn (int $state): string => "{$state} دقيقة")
                    ->sortable(),
            ])
            ->filters([
                Filter::make('invoice_period')
                    ->label('فترة التحصيل')
                    ->form([
                        DatePicker::make('from')
                            ->label('من تاريخ')
                            ->default($clock->nowLocal()->startOfMonth()->toDateString()),
                        DatePicker::make('until')
                            ->label('إلى تاريخ')
                            ->default($clock->nowLocal()->toDateString()),
                    ])
                    ->query(function (Builder $query, array $data) use ($from, $until): Builder {
                        $filterFrom = $data['from'] ?? $from;
                        $filterUntil = $data['until'] ?? $until;

                        return $query->whereHas(
                            'appointmentServices',
                            $this->serviceActivityConstraint($filterFrom, $filterUntil)
                        );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators[] = 'من: '.Carbon::parse($data['from'])->toFormattedDateString();
                        }

                        if ($data['until'] ?? null) {
                            $indicators[] = 'إلى: '.Carbon::parse($data['until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
                SelectFilter::make('branch_id')
                    ->label('الفرع')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('category')
                    ->label('تصنيف الخدمة')
                    ->options(fn (): array => SalonService::query()
                        ->whereNotNull('category')
                        ->distinct()
                        ->orderBy('category')
                        ->pluck('category', 'category')
                        ->all())
                    ->searchable(),
            ], layout: FiltersLayout::AboveContent);
    }

    public function getServicePerformanceQuery(?string $from = null, ?string $until = null): Builder
    {
        $clock = app(TenantClock::class);
        $from ??= $clock->nowLocal()->startOfMonth()->toDateString();
        $until ??= $clock->nowLocal()->toDateString();

        return SalonService::query()
            ->whereHas('appointmentServices', $this->serviceActivityConstraint($from, $until))
            ->withCount([
                'appointmentServices as completed_services_count' => $this->serviceActivityConstraint($from, $until),
            ])
            ->withSum([
                'appointmentServices as recorded_service_value' => $this->serviceActivityConstraint($from, $until),
            ], 'unit_price');
    }

    private function serviceActivityConstraint(string $from, string $until): Closure
    {
        $clock = app(TenantClock::class);
        $fromUtc = $clock->localDateStartUtc($from);
        $untilUtc = $clock->localDateEndUtc($until);

        return fn (Builder $query): Builder => $query->whereHas(
            'appointment',
            fn (Builder $appointmentQuery): Builder => $appointmentQuery
                ->where('status', 'completed')
                ->whereHas(
                    'invoice',
                    fn (Builder $invoiceQuery): Builder => $invoiceQuery
                        ->where('status', 'posted')
                        ->whereNotNull('issued_at')
                        ->whereBetween('issued_at', [$fromUtc, $untilUtc])
                )
        );
    }
}
