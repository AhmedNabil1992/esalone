<?php

namespace App\Filament\Customer\Pages;

use App\Models\Tenant\Invoice;
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
            ->query(Invoice::query()->where('customer_id', auth()->guard('customer')->id())->with(['customer', 'device.type'])->latest('id'))
            ->columns([
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
                TextColumn::make('device.name')
                    ->label('الجهاز')
                    ->placeholder('-')
                    ->searchable(),
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
                    ->modalContent(fn (Invoice $record) => view('filament.customer.pages.invoice-detail-modal', ['invoice' => $record])),
            ])
            ->recordUrl(null)
            ->recordAction('view_details')
            ->defaultSort('id', 'desc');
    }
}
