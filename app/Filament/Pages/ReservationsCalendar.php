<?php

namespace App\Filament\Pages;

use App\Models\Tenant\Devices;
use App\Models\Tenant\Reservation;
use App\Support\Tenant\TenantClock;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Pages\Page;
use Livewire\Attributes\Url;
use UnitEnum;

class ReservationsCalendar extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.reservations-calendar';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static string|UnitEnum|null $navigationGroup = 'اللعب';

    protected static ?string $navigationLabel = 'مخطط الحجوزات';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'مخطط الحجوزات';

    #[Url]
    public string $viewMode = 'day'; // day, week, month

    #[Url]
    public ?string $date = null;

    public function mount(): void
    {
        $this->date = $this->date ?? app(TenantClock::class)->nowLocal()->format('Y-m-d');
    }

    public function next(): void
    {
        $current = Carbon::parse($this->date);
        if ($this->viewMode === 'day') {
            $this->date = $current->addDay()->format('Y-m-d');
        } elseif ($this->viewMode === 'week') {
            $this->date = $current->addWeek()->format('Y-m-d');
        } else {
            $this->date = $current->addMonth()->format('Y-m-d');
        }
    }

    public function prev(): void
    {
        $current = Carbon::parse($this->date);
        if ($this->viewMode === 'day') {
            $this->date = $current->subDay()->format('Y-m-d');
        } elseif ($this->viewMode === 'week') {
            $this->date = $current->subWeek()->format('Y-m-d');
        } else {
            $this->date = $current->subMonth()->format('Y-m-d');
        }
    }

    public function today(): void
    {
        $this->date = app(TenantClock::class)->nowLocal()->format('Y-m-d');
    }

    public function changeView(string $mode): void
    {
        $this->viewMode = $mode;
    }

    public function getHeaderTitle(): string
    {
        $current = Carbon::parse($this->date);

        if ($this->viewMode === 'day') {
            return $this->formatArabicDate($current);
        } elseif ($this->viewMode === 'week') {
            $start = $current->copy()->startOfWeek(Carbon::SATURDAY);
            $end = $start->copy()->addDays(6);

            return 'أسبوع: '.$start->format('d/m').' إلى '.$end->format('d/m/Y');
        } else {
            return $this->getMonthName($current);
        }
    }

    /** @return array<string, mixed> */
    public function getDayViewData(): array
    {
        [$dayStart, $dayEnd] = app(TenantClock::class)->localDateRangeUtc($this->date);
        $devices = Devices::all();
        $reservations = Reservation::query()
            ->whereBetween('start_time', [$dayStart, $dayEnd])
            ->whereIn('status', ['active', 'completed'])
            ->with(['customer', 'playPrice.playType'])
            ->orderBy('start_time')
            ->get()
            ->groupBy('device_id');

        return [
            'devices' => $devices,
            'reservations' => $reservations,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function getWeekViewData(): array
    {
        $startOfWeek = Carbon::parse($this->date)->startOfWeek(Carbon::SATURDAY);
        $clock = app(TenantClock::class);
        [$weekStart, $weekEnd] = $clock->localDateRangeUtc(
            $startOfWeek,
            $startOfWeek->copy()->addDays(6),
        );
        $days = [];

        // Fetch reservations for this week range
        $reservations = Reservation::query()
            ->whereBetween('start_time', [$weekStart, $weekEnd])
            ->whereIn('status', ['active', 'completed'])
            ->with(['device', 'customer', 'playPrice.playType'])
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn ($reservation) => $clock->utcToLocal($reservation->start_time)->format('Y-m-d'));

        for ($i = 0; $i < 7; $i++) {
            $current = $startOfWeek->copy()->addDays($i);
            $days[] = [
                'name' => $this->getArabicDayName($current->dayOfWeek),
                'date' => $current->format('Y-m-d'),
                'display_date' => $current->format('d/m'),
                'reservations' => $reservations->get($current->format('Y-m-d'), collect()),
            ];
        }

        return $days;
    }

    /** @return array<int, array<int, array<string, mixed>|null>> */
    public function getCalendarData(): array
    {
        $date = Carbon::parse($this->date)->startOfMonth();
        $clock = app(TenantClock::class);
        [$monthStart, $monthEnd] = $clock->localDateRangeUtc($date, $date->copy()->endOfMonth());
        $daysInMonth = $date->daysInMonth;

        $firstDayOfMonth = $date->copy()->startOfMonth();
        $dayOfWeek = $firstDayOfMonth->dayOfWeek;

        // Saturday (6) offset mapping
        $offset = ($dayOfWeek + 1) % 7;

        $weeks = [];
        $currentDay = 1;

        // Fetch all reservations for this month
        $reservations = Reservation::query()
            ->whereBetween('start_time', [$monthStart, $monthEnd])
            ->whereIn('status', ['active', 'completed'])
            ->with(['device', 'customer', 'playPrice.playType'])
            ->get()
            ->groupBy(fn ($reservation) => $clock->utcToLocal($reservation->start_time)->day);

        $week = array_fill(0, $offset, null);

        for ($i = $offset; $i < 7; $i++) {
            $week[$i] = [
                'day' => $currentDay,
                'date' => Carbon::create($date->year, $date->month, $currentDay)->format('Y-m-d'),
                'reservations' => $reservations->get($currentDay, collect()),
            ];
            $currentDay++;
        }
        $weeks[] = $week;

        while ($currentDay <= $daysInMonth) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                if ($currentDay <= $daysInMonth) {
                    $week[$i] = [
                        'day' => $currentDay,
                        'date' => Carbon::create($date->year, $date->month, $currentDay)->format('Y-m-d'),
                        'reservations' => $reservations->get($currentDay, collect()),
                    ];
                    $currentDay++;
                } else {
                    $week[$i] = null;
                }
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    private function getMonthName(Carbon $date): string
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        return $months[$date->month].' '.$date->year;
    }

    private function getArabicDayName(int $dayOfWeek): string
    {
        $days = [
            Carbon::SUNDAY => 'الأحد',
            Carbon::MONDAY => 'الإثنين',
            Carbon::TUESDAY => 'الثلاثاء',
            Carbon::WEDNESDAY => 'الأربعاء',
            Carbon::THURSDAY => 'الخميس',
            Carbon::FRIDAY => 'الجمعة',
            Carbon::SATURDAY => 'السبت',
        ];

        return $days[$dayOfWeek] ?? '';
    }

    private function formatArabicDate(Carbon $date): string
    {
        $dayName = $this->getArabicDayName($date->dayOfWeek);
        $monthName = $this->getMonthName($date);

        return $dayName.'، '.$date->day.' '.$monthName;
    }
}
