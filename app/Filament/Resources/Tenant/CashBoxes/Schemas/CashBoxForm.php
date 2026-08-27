<?php

namespace App\Filament\Resources\Tenant\CashBoxes\Schemas;

use App\Models\Tenant\CashBox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class CashBoxForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('الفرع')
                    ->required()
                    ->default(fn () => session('active_branch_id') !== 'all' ? (int) session('active_branch_id') : null)
                    ->searchable()
                    ->preload()
                    ->live(),
                TextInput::make('name')
                    ->label('الإسم')
                    ->required()
                    ->unique(
                        table: 'cash_boxes',
                        column: 'name',
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, callable $get) {
                            $branchId = $get('branch_id');

                            return $branchId ? $rule->where('branch_id', (int) $branchId) : $rule;
                        }
                    ),
                TextInput::make('code')
                    ->label('الرمز (تلقائي)')
                    ->default(function (): string {
                        $maxCode = CashBox::withoutGlobalScopes()->max('code');

                        return (string) (((int) $maxCode) + 1);
                    })
                    ->readOnly()
                    ->dehydrated()
                    ->required(),
                Select::make('type')->label('النوع')->options([
                    'cash' => 'كاش',
                    'bank' => 'بنك',
                    'wallet' => 'محفظة إلكترونية',
                ])->required(),
                TextInput::make('opening_balance')->label('الرصيد الافتتاحي')->numeric()->required()->default(0)->minValue(0),
            ]);
    }
}
