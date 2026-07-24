<?php

namespace App\Filament\Pages;

use App\Models\Tenant\ExpenseHistory;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Purchase;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class ProfitLossReport extends Page
{
    use HasPageShield;

    protected static ?string $title = 'الأرباح والخسائر';

    protected static ?string $navigationLabel = 'الأرباح والخسائر';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'تقارير إدارية';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.profit-loss-report';

    public ?string $from = null;

    public ?string $until = null;

    public function mount(): void
    {
        $this->from = Carbon::now()->startOfMonth()->toDateString();
        $this->until = Carbon::today()->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    public function getFinancials(): array
    {
        $playRevenue = (float) Invoice::query()
            ->where('status', 'posted')
            ->when($this->from, fn ($q) => $q->whereDate('issued_at', '>=', $this->from))
            ->when($this->until, fn ($q) => $q->whereDate('issued_at', '<=', $this->until))
            ->sum('play_amount');

        $cafeRevenue = (float) Invoice::query()
            ->where('status', 'posted')
            ->when($this->from, fn ($q) => $q->whereDate('issued_at', '>=', $this->from))
            ->when($this->until, fn ($q) => $q->whereDate('issued_at', '<=', $this->until))
            ->sum('order_amount');

        $discounts = (float) Invoice::query()
            ->where('status', 'posted')
            ->when($this->from, fn ($q) => $q->whereDate('issued_at', '>=', $this->from))
            ->when($this->until, fn ($q) => $q->whereDate('issued_at', '<=', $this->until))
            ->sum('discount_amount');

        $serviceAmount = (float) Invoice::query()
            ->where('status', 'posted')
            ->when($this->from, fn ($q) => $q->whereDate('issued_at', '>=', $this->from))
            ->when($this->until, fn ($q) => $q->whereDate('issued_at', '<=', $this->until))
            ->sum('service_amount');

        $taxAmount = (float) Invoice::query()
            ->where('status', 'posted')
            ->when($this->from, fn ($q) => $q->whereDate('issued_at', '>=', $this->from))
            ->when($this->until, fn ($q) => $q->whereDate('issued_at', '<=', $this->until))
            ->sum('tax_amount');

        $totalRevenue = (float) Invoice::query()
            ->where('status', 'posted')
            ->when($this->from, fn ($q) => $q->whereDate('issued_at', '>=', $this->from))
            ->when($this->until, fn ($q) => $q->whereDate('issued_at', '<=', $this->until))
            ->sum('final_amount');

        $expenses = (float) ExpenseHistory::query()
            ->when($this->from, fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->until, fn ($q) => $q->whereDate('created_at', '<=', $this->until))
            ->sum('amount');

        $purchases = (float) Purchase::query()
            ->where('status', 'posted')
            ->when($this->from, fn ($q) => $q->whereDate('purchased_at', '>=', $this->from))
            ->when($this->until, fn ($q) => $q->whereDate('purchased_at', '<=', $this->until))
            ->sum('total_amount');

        $netProfit = $totalRevenue - $expenses - $purchases;

        // Breakdown of expenses by type
        $expensesByType = ExpenseHistory::query()
            ->select('expenses_id', DB::raw('SUM(amount) as total_amount'))
            ->when($this->from, fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->until, fn ($q) => $q->whereDate('created_at', '<=', $this->until))
            ->groupBy('expenses_id')
            ->with('expenseType')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->expenseType?->name ?? 'مصاريف عامة / تشغيلية أخرى',
                'amount' => (float) $row->total_amount,
            ])
            ->toArray();

        // Breakdown of purchases by supplier
        $purchasesBySupplier = Purchase::query()
            ->select('supplier_id', DB::raw('SUM(total_amount) as total_amount'))
            ->where('status', 'posted')
            ->when($this->from, fn ($q) => $q->whereDate('purchased_at', '>=', $this->from))
            ->when($this->until, fn ($q) => $q->whereDate('purchased_at', '<=', $this->until))
            ->groupBy('supplier_id')
            ->with('supplier')
            ->get()
            ->map(fn ($row) => [
                'supplier' => $row->supplier?->name ?? 'مشتريات عامة/نثرية',
                'amount' => (float) $row->total_amount,
            ])
            ->toArray();

        return [
            'play_revenue' => $playRevenue,
            'cafe_revenue' => $cafeRevenue,
            'discounts' => $discounts,
            'service_amount' => $serviceAmount,
            'tax_amount' => $taxAmount,
            'total_revenue' => $totalRevenue,
            'expenses' => $expenses,
            'purchases' => $purchases,
            'net_profit' => $netProfit,
            'expenses_breakdown' => $expensesByType,
            'purchases_breakdown' => $purchasesBySupplier,
        ];
    }
}
