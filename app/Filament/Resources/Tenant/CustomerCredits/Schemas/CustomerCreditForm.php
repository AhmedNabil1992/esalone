<?php

namespace App\Filament\Resources\Tenant\CustomerCredits\Schemas;

use App\Models\Tenant\CashBox;
use App\Models\Tenant\Customer;
use App\Models\Tenant\SystemConfig as Setting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerCreditForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('customer_id')
                ->label('العميل')
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => Customer::query()
                    ->where('is_active', true)
                    ->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->limit(50)
                    ->get()
                    ->mapWithKeys(fn ($customer) => [$customer->id => "{$customer->name} ({$customer->phone})"])
                    ->toArray()
                )
                ->getOptionLabelUsing(fn ($value): ?string => Customer::find($value) ? (Customer::find($value)->name.' ('.Customer::find($value)->phone.')') : null)
                ->required(),

            Select::make('type')
                ->label('نوع العملية')
                ->options([
                    'deposit' => 'إيداع (إضافة رصيد)',
                    'withdraw' => 'سحب (خصم رصيد)',
                    'redeem' => 'تبديل نقاط',
                ])
                ->required(),

            TextInput::make('amount')
                ->label('المبلغ')
                ->numeric()
                ->minValue(0.01)
                ->required(),

            Select::make('cash_box_id')
                ->label('الخزنة')
                ->options(fn () => CashBox::query()
                    ->where('is_active', true)
                    ->orderBy('name', 'asc')
                    ->pluck('name', 'id')
                    ->toArray())
                ->default(function (): ?int {
                    $defaultCashBoxId = (int) Setting::get('sys-default-cashbox', 0);

                    if ($defaultCashBoxId > 0) {
                        return $defaultCashBoxId;
                    }

                    return CashBox::query()
                        ->where('is_active', true)
                        ->value('id');
                })
                ->searchable()
                ->preload()
                ->required(),

            Textarea::make('description')
                ->label('ملاحظة')
                ->rows(2),
        ]);
    }
}
