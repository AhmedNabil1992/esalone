<?php

namespace App\Filament\Pages;

use App\Models\Tenant\ExpenseHistory;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\MarketHistory;
use App\Models\Tenant\Purchase;
use App\Support\Tenant\TenantClock;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
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
        $this->from = app(TenantClock::class)->nowLocal()->startOfMonth()->toDateString();
        $this->until = app(TenantClock::class)->nowLocal()->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    public function getFinancials(): array
    {
        $clock = app(TenantClock::class);
        $fromUtc = $this->from ? $clock->localDateStartUtc($this->from) : null;
        $untilUtc = $this->until ? $clock->localDateEndUtc($this->until) : null;

        $postedInvoices = Invoice::query()
            ->where('status', 'posted')
            ->when($fromUtc, fn ($q) => $q->where('issued_at', '>=', $fromUtc))
            ->when($untilUtc, fn ($q) => $q->where('issued_at', '<=', $untilUtc));

        $appointmentRevenue = (float) (clone $postedInvoices)
            ->whereHas('appointment')
            ->sum('final_amount');

        $otherSalesRevenue = (float) (clone $postedInvoices)
            ->whereDoesntHave('appointment')
            ->sum('final_amount');

        $discounts = (float) Invoice::query()
            ->where('status', 'posted')
            ->when($fromUtc, fn ($q) => $q->where('issued_at', '>=', $fromUtc))
            ->when($untilUtc, fn ($q) => $q->where('issued_at', '<=', $untilUtc))
            ->sum('discount_amount');

        $serviceAmount = (float) Invoice::query()
            ->where('status', 'posted')
            ->when($fromUtc, fn ($q) => $q->where('issued_at', '>=', $fromUtc))
            ->when($untilUtc, fn ($q) => $q->where('issued_at', '<=', $untilUtc))
            ->sum('service_amount');

        $taxAmount = (float) Invoice::query()
            ->where('status', 'posted')
            ->when($fromUtc, fn ($q) => $q->where('issued_at', '>=', $fromUtc))
            ->when($untilUtc, fn ($q) => $q->where('issued_at', '<=', $untilUtc))
            ->sum('tax_amount');

        $totalRevenue = (float) (clone $postedInvoices)->sum('final_amount');
        $costOfSales = (float) MarketHistory::query()
            ->whereIn('invoice_no', (clone $postedInvoices)->select('invoice_no'))
            ->sum('total_cost');

        $expenses = (float) ExpenseHistory::query()
            ->when($fromUtc, fn ($q) => $q->where('created_at', '>=', $fromUtc))
            ->when($untilUtc, fn ($q) => $q->where('created_at', '<=', $untilUtc))
            ->sum('amount');

        $purchases = (float) Purchase::query()
            ->where('status', 'posted')
            ->when($fromUtc, fn ($q) => $q->where('purchased_at', '>=', $fromUtc))
            ->when($untilUtc, fn ($q) => $q->where('purchased_at', '<=', $untilUtc))
            ->sum('total_amount');

        $netProfit = $totalRevenue - $taxAmount - $expenses - $costOfSales;

        // Breakdown of expenses by type
        $expensesByType = ExpenseHistory::query()
            ->select('expenses_id', DB::raw('SUM(amount) as total_amount'))
            ->when($fromUtc, fn ($q) => $q->where('created_at', '>=', $fromUtc))
            ->when($untilUtc, fn ($q) => $q->where('created_at', '<=', $untilUtc))
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
            ->when($fromUtc, fn ($q) => $q->where('purchased_at', '>=', $fromUtc))
            ->when($untilUtc, fn ($q) => $q->where('purchased_at', '<=', $untilUtc))
            ->groupBy('supplier_id')
            ->with('supplier')
            ->get()
            ->map(fn ($row) => [
                'supplier' => $row->supplier?->name ?? 'مشتريات عامة/نثرية',
                'amount' => (float) $row->total_amount,
            ])
            ->toArray();

        return [
            'appointment_revenue' => $appointmentRevenue,
            'other_sales_revenue' => $otherSalesRevenue,
            'discounts' => $discounts,
            'service_amount' => $serviceAmount,
            'tax_amount' => $taxAmount,
            'total_revenue' => $totalRevenue,
            'expenses' => $expenses,
            'purchases' => $purchases,
            'cost_of_sales' => $costOfSales,
            'net_profit' => $netProfit,
            'expenses_breakdown' => $expensesByType,
            'purchases_breakdown' => $purchasesBySupplier,
        ];
    }
}
