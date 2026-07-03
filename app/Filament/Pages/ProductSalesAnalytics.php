<?php

namespace App\Filament\Pages;

use App\Models\Tenant\CatalogItem;
use App\Models\Tenant\MarketHistory;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class ProductSalesAnalytics extends Page
{
    protected static ?string $title = 'إحصائيات مبيعات الكافيه والطلبات';

    protected static ?string $navigationLabel = 'إحصائيات المبيعات والطلبات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|\UnitEnum|null $navigationGroup = 'الإحصائيات';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.product-sales-analytics';

    public ?string $from = null;

    public ?string $until = null;

    public string $chartDataSerialized = '';

    public function mount(): void
    {
        $this->from = Carbon::now()->startOfMonth()->toDateString();
        $this->until = Carbon::today()->toDateString();
        $this->updateChartData();
    }

    public function updated($propertyName): void
    {
        if (in_array($propertyName, ['from', 'until'])) {
            $this->updateChartData();
        }
    }

    public function updateChartData(): void
    {
        $stats = $this->getProductSalesStats();
        $topItems = array_slice($stats, 0, 10);
        $labels = [];
        $qty = [];
        $rev = [];
        foreach ($topItems as $item) {
            $labels[] = $item['name'];
            $qty[] = $item['quantity_sold'];
            $rev[] = $item['total_revenue'];
        }
        $this->chartDataSerialized = json_encode([
            'labels' => $labels,
            'qty' => $qty,
            'rev' => $rev,
        ]);
    }

    /**
     * @return array<int, array{item_id: int, name: string, quantity_sold: int, total_revenue: float, unit_price: float}>
     */
    public function getProductSalesStats(): array
    {
        $sales = MarketHistory::query()
            ->select(
                'catalog_item_id',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw('MAX(unit_price) as last_price')
            )
            ->when($this->from, fn ($q) => $q->whereDate('transaction_date', '>=', $this->from))
            ->when($this->until, fn ($q) => $q->whereDate('transaction_date', '<=', $this->until))
            ->groupBy('catalog_item_id')
            ->with('catalogItem')
            ->get();

        $stats = [];
        foreach ($sales as $row) {
            $itemId = (int) $row->catalog_item_id;
            $itemName = $row->catalogItem?->name ?? $row->subject_name ?? 'صنف مجهول';

            $stats[] = [
                'item_id' => $itemId,
                'name' => $itemName,
                'quantity_sold' => (int) $row->total_qty,
                'total_revenue' => (float) $row->total_revenue,
                'unit_price' => (float) $row->last_price,
            ];
        }

        // Sort by quantity sold descending
        usort($stats, fn ($a, $b) => $b['quantity_sold'] <=> $a['quantity_sold']);

        return $stats;
    }

    /**
     * @return array<int, array{name: string, price: float}>
     */
    public function getUnsoldProducts(): array
    {
        $soldItemIds = MarketHistory::query()
            ->when($this->from, fn ($q) => $q->whereDate('transaction_date', '>=', $this->from))
            ->when($this->until, fn ($q) => $q->whereDate('transaction_date', '<=', $this->until))
            ->pluck('catalog_item_id')
            ->unique()
            ->filter()
            ->toArray();

        return CatalogItem::query()
            ->where('is_active', true)
            ->whereNotIn('id', $soldItemIds)
            ->get()
            ->map(fn ($item) => [
                'name' => (string) $item->name,
                'price' => (float) $item->sale_price,
            ])
            ->toArray();
    }
}
