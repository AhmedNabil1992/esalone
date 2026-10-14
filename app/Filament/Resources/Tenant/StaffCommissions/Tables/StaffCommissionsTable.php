<?php

namespace App\Filament\Resources\Tenant\StaffCommissions\Tables;

use App\Actions\Tenant\RecordStaffCommissionPayoutAction;
use App\Models\Tenant\CashBox;
use App\Models\Tenant\StaffCommission;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StaffCommissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('appointment.start_at')
                    ->label('تاريخ الموعد')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('staffMember.name')
                    ->label('الموظف')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice.invoice_no')
                    ->label('الفاتورة')
                    ->searchable(),
                TextColumn::make('commission_rate')
                    ->label('النسبة')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('commission_base')
                    ->label('قيمة الخدمات بعد الخصم')
                    ->money()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('إجمالي العمولة')
                    ->money()
                    ->sortable(),
                TextColumn::make('payouts_sum_amount')
                    ->label('المصروف')
                    ->money()
                    ->default(0),
                TextColumn::make('remaining_amount')
                    ->label('المتبقي')
                    ->money()
                    ->color(fn (float $state): string => $state > 0 ? 'warning' : 'success'),
            ])
            ->recordActions([
                Action::make('recordPayout')
                    ->label('تسجيل صرف')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->authorize('payout')
                    ->visible(fn (StaffCommission $record): bool => $record->remaining_amount > 0)
                    ->schema([
                        TextInput::make('amount')
                            ->label('مبلغ الصرف')
                            ->numeric()
                            ->minValue(0.01)
                            ->maxValue(fn (StaffCommission $record): float => $record->remaining_amount)
                            ->step(0.01)
                            ->required(),
                        Select::make('cash_box_id')
                            ->label('الخزينة')
                            ->options(fn (StaffCommission $record): array => CashBox::query()
                                ->where('branch_id', $record->branch_id)
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                    ])
                    ->modalHeading('تسجيل صرف عمولة')
                    ->modalSubmitActionLabel('تأكيد الصرف')
                    ->action(function (StaffCommission $record, array $data): void {
                        app(RecordStaffCommissionPayoutAction::class)->execute(
                            $record,
                            (int) $data['cash_box_id'],
                            (float) $data['amount'],
                        );

                        Notification::make()
                            ->title('تم تسجيل صرف العمولة')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
