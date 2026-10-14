<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Tenant\Appointment;
use App\Support\Tenant\TenantClock;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class AppointmentOutcomesReport extends Page
{
    use HasPageShield;

    protected static ?string $title = 'تقرير نتائج المواعيد';

    protected static ?string $navigationLabel = 'نتائج المواعيد';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup = 'تقارير إدارية';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.appointment-outcomes-report';

    public ?string $from = null;

    public ?string $until = null;

    public string $appliedFrom = '';

    public string $appliedUntil = '';

    public function mount(): void
    {
        $clock = app(TenantClock::class);
        $this->from = $clock->nowLocal()->startOfMonth()->toDateString();
        $this->until = $clock->nowLocal()->toDateString();
        $this->appliedFrom = $this->from;
        $this->appliedUntil = $this->until;
    }

    public function applyFilters(): void
    {
        $filters = Validator::make([
            'from' => $this->from,
            'until' => $this->until,
        ], [
            'from' => ['required', 'date_format:Y-m-d'],
            'until' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ])->validate();

        $this->appliedFrom = $filters['from'];
        $this->appliedUntil = $filters['until'];
    }

    /**
     * @return array{
     *     total: int,
     *     completed: int,
     *     collected: int,
     *     completed_without_invoice: int,
     *     completion_rate: float,
     *     booking_collection_rate: float,
     *     completion_collection_rate: float,
     *     statuses: Collection<int, array{status: string, label: string, count: int}>
     * }
     */
    public function getReportData(): array
    {
        $periodAppointments = $this->appointmentsInPeriod();
        $statusCounts = (clone $periodAppointments)
            ->select('status')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $total = (int) $statusCounts->sum('aggregate');
        $completed = (int) ($statusCounts->get('completed')?->aggregate ?? 0);
        $collected = (clone $periodAppointments)
            ->where('status', 'completed')
            ->whereHas('invoice', fn (Builder $query): Builder => $query
                ->where('status', 'posted')
                ->whereNotNull('issued_at'))
            ->count();

        $statuses = $statusCounts
            ->map(fn ($row): array => [
                'status' => (string) $row->status,
                'label' => self::statusLabel((string) $row->status),
                'count' => (int) $row->aggregate,
            ])
            ->sortBy(fn (array $row): string => $row['label'])
            ->values();

        return [
            'total' => $total,
            'completed' => $completed,
            'collected' => $collected,
            'completed_without_invoice' => max(0, $completed - $collected),
            'completion_rate' => $this->percentage($completed, $total),
            'booking_collection_rate' => $this->percentage($collected, $total),
            'completion_collection_rate' => $this->percentage($collected, $completed),
            'statuses' => $statuses,
        ];
    }

    private function appointmentsInPeriod(): Builder
    {
        $clock = app(TenantClock::class);

        return Appointment::query()
            ->whereBetween('start_at', [
                $clock->localDateStartUtc($this->appliedFrom),
                $clock->localDateEndUtc($this->appliedUntil),
            ]);
    }

    private static function statusLabel(string $status): string
    {
        return [
            'requested' => 'طلب جديد',
            'scheduled' => 'مجدول',
            'confirmed' => 'مؤكد',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            'no_show' => 'لم يحضر',
        ][$status] ?? $status;
    }

    private function percentage(int $numerator, int $denominator): float
    {
        if ($denominator === 0) {
            return 0;
        }

        return round($numerator / $denominator * 100, 1);
    }
}
