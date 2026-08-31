<?php

namespace App\Filament\Customer\Pages;

use App\Models\Tenant\Invoice;
use App\Scopes\BranchScope;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class CustomerInvoices extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'الفواتير';

    protected static ?string $navigationLabel = 'الفواتير';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.customer.pages.customer-invoices';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::withoutGlobalScope(BranchScope::class)
                    ->where('customer_id', auth()->guard('customer')->id())
                    ->with([
                        'customer',
                        'branch',
                        'device' => fn ($q) => $q->withoutGlobalScope(BranchScope::class)->with('type'),
                        'playSessionDetails' => fn ($q) => $q->withoutGlobalScope(BranchScope::class)->with([
                            'device' => fn ($dq) => $dq->withoutGlobalScope(BranchScope::class),
                        ]),
                        'marketHistories' => fn ($q) => $q->withoutGlobalScope(BranchScope::class)->with(['catalogItem', 'item']),
                    ])
                    ->latest('id')
            )
            ->columns([
                TextColumn::make('branch.name')
                    ->label('الفرع')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('invoice_no')
                    ->label('رقم الفاتورة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_type')
                    ->label('نوع الفاتورة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'direct_sale' => 'بيع مباشر',
                        'device_sale' => 'لعب على الأجهزة',
                        default => '-',
                    })
                    ->color(fn (?string $state): string => $state === 'direct_sale' ? 'success' : 'primary'),
                TextColumn::make('issued_at')
                    ->label('التاريخ')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),
                TextColumn::make('device_display_name')
                    ->label('الجهاز')
                    ->state(function (Invoice $record): string {
                        if ($record->device?->name) {
                            return $record->device->name;
                        }
                        $firstPlayDevice = $record->playSessionDetails->first()?->device?->name;
                        if ($firstPlayDevice) {
                            return $firstPlayDevice;
                        }
                        $firstMarketDevice = $record->marketHistories->first()?->device?->name;
                        if ($firstMarketDevice) {
                            return $firstMarketDevice;
                        }

                        return '-';
                    })
                    ->placeholder('-')
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('device', fn ($q) => $q->withoutGlobalScope(BranchScope::class)->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('playSessionDetails.device', fn ($q) => $q->withoutGlobalScope(BranchScope::class)->where('name', 'like', "%{$search}%"));
                    }),
                TextColumn::make('final_amount')
                    ->label('الصافي')
                    ->money()
                    ->sortable(),
                TextColumn::make('paid_amount')
                    ->label('المدفوع')
                    ->money()
                    ->sortable(),
            ])
            ->actions([
                Action::make('view_details')
                    ->label('تفاصيل')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalHeading('تفاصيل الفاتورة')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalContent(function (Invoice $record) {
                        $record->load([
                            'playSessionDetails' => fn ($q) => $q->withoutGlobalScope(BranchScope::class)->with([
                                'device' => fn ($dq) => $dq->withoutGlobalScope(BranchScope::class),
                            ]),
                            'marketHistories' => fn ($q) => $q->withoutGlobalScope(BranchScope::class)->with(['catalogItem', 'item']),
                            'device' => fn ($q) => $q->withoutGlobalScope(BranchScope::class),
                            'branch',
                        ]);

                        return view('filament.customer.pages.invoice-detail-modal', [
                            'invoice' => $record,
                        ]);
                    }),
            ])
            ->recordUrl(null)
            ->recordAction('view_details')
            ->defaultSort('id', 'desc');
    }
}
