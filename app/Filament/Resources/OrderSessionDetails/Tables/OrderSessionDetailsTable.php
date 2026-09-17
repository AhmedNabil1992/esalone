<?php

namespace App\Filament\Resources\OrderSessionDetails\Tables;

use App\Actions\Tenant\ManageSessionOrderAction;
use App\Models\Tenant\OrderSessionDetail;
use App\Models\Tenant\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use RuntimeException;
use Throwable;

class OrderSessionDetailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('device.type.name')
                    ->label('نوع الجهاز')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('device.name')
                    ->label('اسم الجهاز')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('catalogItem.inventoryGroup.name')
                    ->label('مجموعة الصنف')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('catalogItem.name')
                    ->label('اسم المنتج')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('price')
                    ->label('سعر المنتج')
                    ->money()
                    ->placeholder('-'),
                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->placeholder('-'),
                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->money()
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->placeholder('-')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'قيد الانتظار',
                        'completed' => 'مكتمل',
                        'canceled' => 'ملغي',
                        default => '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'canceled' => 'danger',
                        default => 'secondary',
                    }),
                TextColumn::make('order_by')
                    ->label('تم الطلب بواسطة')
                    ->placeholder('-'),
                TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->placeholder('-'),
                TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->placeholder('-'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('update_quantity')
                    ->label('تعديل الكمية')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn (OrderSessionDetail $record): bool => self::canManage($record, ManageSessionOrderAction::UPDATE_PERMISSION))
                    ->fillForm(fn (OrderSessionDetail $record): array => [
                        'quantity' => (int) $record->quantity,
                    ])
                    ->schema([
                        TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->required(),
                    ])
                    ->action(function (OrderSessionDetail $record, array $data): void {
                        try {
                            app(ManageSessionOrderAction::class)->updateQuantity(
                                (int) $record->getKey(),
                                (int) $data['quantity'],
                                self::authenticatedUser(),
                            );

                            Notification::make()
                                ->title('تم تحديث كمية الطلب')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            self::sendFailureNotification('تعذر تحديث الطلب', $exception);
                        }
                    }),
                Action::make('delete_order')
                    ->label('حذف')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('حذف الطلب')
                    ->modalDescription('هل أنت متأكد من حذف هذا الطلب؟')
                    ->visible(fn (OrderSessionDetail $record): bool => self::canManage($record, ManageSessionOrderAction::DELETE_PERMISSION))
                    ->action(function (OrderSessionDetail $record): void {
                        try {
                            app(ManageSessionOrderAction::class)->delete((int) $record->getKey(), self::authenticatedUser());

                            Notification::make()
                                ->title('تم حذف الطلب')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            self::sendFailureNotification('تعذر حذف الطلب', $exception);
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function canManage(OrderSessionDetail $record, string $permission): bool
    {
        return auth()->user()?->can($permission) === true
            && $record->status === 'pending'
            && $record->playSession()->where('status', 'active')->exists();
    }

    private static function authenticatedUser(): User
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            throw new RuntimeException('غير مصرح لك بتنفيذ هذا الإجراء.');
        }

        return $user;
    }

    private static function sendFailureNotification(string $title, Throwable $exception): void
    {
        Notification::make()
            ->title($title)
            ->body($exception->getMessage())
            ->danger()
            ->send();
    }
}
