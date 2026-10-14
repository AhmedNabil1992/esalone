<?php

namespace App\Filament\Resources\Tenant\Appointments\Tables;

use App\Actions\Tenant\CheckoutAppointmentAction;
use App\Models\Tenant\Appointment;
use App\Models\Tenant\SystemConfig;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('start_at')
                    ->label('موعد الحضور')
                    ->dateTime('Y-m-d h:i A')
                    ->timezone(SystemConfig::get('sys_system_time_zone', 'Africa/Cairo'))
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->placeholder('بدون تسجيل')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contact_name')
                    ->label('اسم صاحب الطلب')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('contact_phone')
                    ->label('رقم التواصل')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('staffMember.name')
                    ->label('الموظف')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('items.service_name')
                    ->label('الخدمات')
                    ->badge()
                    ->separator('،'),
                TextColumn::make('duration_minutes')
                    ->label('المدة')
                    ->suffix(' دقيقة')
                    ->sortable(),
                TextColumn::make('total_price')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::statusLabel($state))
                    ->color(fn (string $state): string => match ($state) {
                        'requested' => 'warning',
                        'scheduled' => 'gray',
                        'confirmed' => 'info',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled', 'no_show' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('invoice.invoice_no')
                    ->label('رقم الفاتورة')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'requested' => 'طلب حجز جديد',
                        'scheduled' => 'محجوز',
                        'confirmed' => 'مؤكد',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                        'no_show' => 'لم يحضر',
                    ]),
            ])
            ->recordActions([
                Action::make('confirm')
                    ->label('تأكيد الموعد')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->authorize('update')
                    ->visible(fn (Appointment $record): bool => $record->status === 'requested')
                    ->requiresConfirmation()
                    ->action(fn (Appointment $record) => $record->update(['status' => 'confirmed'])),
                Action::make('complete')
                    ->label('إكمال الخدمة')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->authorize('update')
                    ->visible(fn (Appointment $record): bool => in_array($record->status, ['scheduled', 'confirmed', 'in_progress'], true))
                    ->requiresConfirmation()
                    ->action(fn (Appointment $record) => $record->update(['status' => 'completed'])),
                Action::make('cancel')
                    ->label('إلغاء الموعد')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->authorize('update')
                    ->visible(fn (Appointment $record): bool => in_array($record->status, ['requested', 'scheduled', 'confirmed'], true))
                    ->requiresConfirmation()
                    ->action(fn (Appointment $record) => $record->update(['status' => 'cancelled'])),
                Action::make('checkout')
                    ->label('تحصيل عبر نقطة البيع')
                    ->icon('heroicon-o-banknotes')
                    ->color('primary')
                    ->authorize('update')
                    ->visible(fn (Appointment $record): bool => $record->status === 'completed' && $record->invoice_id === null)
                    ->requiresConfirmation()
                    ->action(function (Appointment $record): void {
                        try {
                            $invoice = app(CheckoutAppointmentAction::class)->execute($record);
                        } catch (ValidationException|RuntimeException $exception) {
                            Notification::make()
                                ->title('تعذر تحصيل الموعد')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title("تم تحصيل الموعد وإصدار الفاتورة {$invoice->invoice_no}")
                            ->success()
                            ->send();
                    }),
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Appointment $record): bool => $record->invoice_id === null),
            ])
            ->defaultSort('start_at', 'desc');
    }

    private static function statusLabel(string $state): string
    {
        return match ($state) {
            'requested' => 'طلب حجز جديد',
            'scheduled' => 'محجوز',
            'confirmed' => 'مؤكد',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            'no_show' => 'لم يحضر',
            default => $state,
        };
    }
}
