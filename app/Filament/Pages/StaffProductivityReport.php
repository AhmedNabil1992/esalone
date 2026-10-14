<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Tenant\Appointment;
use App\Models\Tenant\StaffCommission;
use App\Models\Tenant\StaffMember;
use App\Support\Tenant\TenantClock;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class StaffProductivityReport extends Page
{
    use HasPageShield;

    private const array OCCUPANCY_STATUSES = ['scheduled', 'confirmed', 'in_progress', 'completed'];

    protected static ?string $title = 'تقرير إنتاجية الموظفين وإشغال المواعيد';

    protected static ?string $navigationLabel = 'إنتاجية الموظفين';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'تقارير إدارية';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.staff-productivity-report';

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
     * @return Collection<int, array{
     *     name: string,
     *     job_title: ?string,
     *     appointments: int,
     *     completed: int,
     *     collected_appointments: int,
     *     collected_revenue: float,
     *     commission: float,
     *     occupied_minutes: int,
     *     available_minutes: ?int,
     *     occupancy_rate: ?float
     * }>
     */
    public function getStaffProductivityRows(): Collection
    {
        $appointmentPeriod = $this->appointmentPeriod();
        $staff = StaffMember::query()
            ->withCount([
                'appointments as appointments_count' => fn (Builder $query): Builder => $query
                    ->whereBetween('start_at', $this->utcDateRange()),
                'appointments as completed_count' => fn (Builder $query): Builder => $query
                    ->where('status', 'completed')
                    ->whereBetween('start_at', $this->utcDateRange()),
                'appointments as collected_appointments_count' => fn (Builder $query): Builder => $query
                    ->where('status', 'completed')
                    ->whereBetween('start_at', $this->utcDateRange())
                    ->whereHas('invoice', fn (Builder $invoiceQuery): Builder => $invoiceQuery
                        ->where('status', 'posted')
                        ->whereNotNull('issued_at')),
            ])
            ->withSum([
                'appointments as occupied_minutes' => fn (Builder $query): Builder => $query
                    ->whereIn('status', self::OCCUPANCY_STATUSES)
                    ->whereBetween('start_at', $this->utcDateRange()),
            ], 'duration_minutes')
            ->orderBy('name')
            ->get();

        $collectedRevenues = (clone $appointmentPeriod)
            ->join('invoices', function ($join): void {
                $join->on('invoices.id', '=', 'appointments.invoice_id')
                    ->on('invoices.branch_id', '=', 'appointments.branch_id');
            })
            ->where('appointments.status', 'completed')
            ->where('invoices.status', 'posted')
            ->whereNotNull('invoices.issued_at')
            ->select('appointments.staff_member_id')
            ->selectRaw('SUM(invoices.final_amount) as collected_revenue')
            ->groupBy('appointments.staff_member_id')
            ->get()
            ->keyBy('staff_member_id');

        $commissions = StaffCommission::query()
            ->whereHas('invoice', fn (Builder $query): Builder => $query
                ->where('status', 'posted')
                ->whereNotNull('issued_at'))
            ->whereHas('appointment', fn (Builder $query): Builder => $query
                ->whereBetween('start_at', $this->utcDateRange()))
            ->select('staff_member_id')
            ->selectRaw('SUM(amount) as commission_total')
            ->groupBy('staff_member_id')
            ->get()
            ->keyBy('staff_member_id');

        return $staff->map(function (StaffMember $member) use ($collectedRevenues, $commissions): array {
            $schedule = $member->weekly_schedule ?? [];
            $availableMinutes = $this->availableMinutes($schedule);
            $occupiedMinutes = (int) ($member->occupied_minutes ?? 0);

            return [
                'name' => $member->name,
                'job_title' => $member->job_title,
                'appointments' => (int) $member->appointments_count,
                'completed' => (int) $member->completed_count,
                'collected_appointments' => (int) $member->collected_appointments_count,
                'collected_revenue' => (float) ($collectedRevenues->get($member->getKey())?->collected_revenue ?? 0),
                'commission' => (float) ($commissions->get($member->getKey())?->commission_total ?? 0),
                'occupied_minutes' => $occupiedMinutes,
                'available_minutes' => $availableMinutes,
                'occupancy_rate' => $availableMinutes
                    ? round($occupiedMinutes / $availableMinutes * 100, 1)
                    : null,
            ];
        });
    }

    private function appointmentPeriod(): Builder
    {
        return Appointment::query()->whereBetween('start_at', $this->utcDateRange());
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function utcDateRange(): array
    {
        $clock = app(TenantClock::class);

        return [
            $clock->localDateStartUtc($this->appliedFrom),
            $clock->localDateEndUtc($this->appliedUntil),
        ];
    }

    /**
     * @param  array<int, array{day_of_week?: mixed, opens_at?: mixed, closes_at?: mixed}>  $schedule
     */
    private function availableMinutes(array $schedule): ?int
    {
        if ($schedule === []) {
            return null;
        }

        $clock = app(TenantClock::class);
        $from = CarbonImmutable::parse($this->appliedFrom, $clock->timezone());
        $until = CarbonImmutable::parse($this->appliedUntil, $clock->timezone());
        $days = (int) $from->diffInDays($until) + 1;

        if ($days <= 0) {
            return null;
        }

        $fullWeeks = intdiv($days, 7);
        $remainingDays = $days % 7;
        $availableMinutes = 0;

        foreach ($schedule as $slot) {
            if (! is_array($slot)) {
                continue;
            }

            $dayOfWeek = $slot['day_of_week'] ?? null;
            $opensAt = $slot['opens_at'] ?? null;
            $closesAt = $slot['closes_at'] ?? null;

            if (! is_numeric($dayOfWeek)
                || (int) $dayOfWeek < 0
                || (int) $dayOfWeek > 6
                || ! is_string($opensAt)
                || ! is_string($closesAt)
                || ! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $opensAt)
                || ! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $closesAt)
                || $opensAt >= $closesAt) {
                continue;
            }

            [$openHour, $openMinute] = array_map('intval', explode(':', $opensAt));
            [$closeHour, $closeMinute] = array_map('intval', explode(':', $closesAt));
            $slotMinutes = ($closeHour * 60 + $closeMinute) - ($openHour * 60 + $openMinute);
            $availableMinutes += $slotMinutes * $fullWeeks;

            for ($day = 0; $day < $remainingDays; $day++) {
                if ($from->addDays($day)->dayOfWeek === (int) $dayOfWeek) {
                    $availableMinutes += $slotMinutes;
                }
            }
        }

        return $availableMinutes > 0 ? $availableMinutes : null;
    }
}
