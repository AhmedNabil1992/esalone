<?php

namespace App\Filament\Resources\Tenant\Customers\Tables;

use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerCredit;
use App\Models\Tenant\SystemConfig;
use App\Models\Tenant\User;
use App\Support\Tenant\WhatsAppPhoneNumber;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable(),

                TextColumn::make('level.name')
                    ->label('المستوى / الفئة')
                    ->placeholder('عميل عادي')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('email')
                    ->label('البريد')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('balance')
                    ->label('الرصيد')
                    ->money()
                    ->sortable()
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray')),
                TextColumn::make('points')
                    ->label('النقاط')
                    ->sortable()
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray')),

                TextColumn::make('invoices_max_issued_at')
                    ->label('آخر زيارة')
                    ->date('Y-m-d')
                    ->placeholder('لا توجد زيارات')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('مفعّل')
                    ->onColor('success')
                    ->offColor('danger'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Filter::make('needs_follow_up')
                    ->label('عملاء يحتاجون متابعة')
                    ->form([
                        Select::make('inactive_days')
                            ->label('مدة عدم الزيارة')
                            ->options([
                                30 => '30 يومًا',
                                60 => '60 يومًا',
                                90 => '90 يومًا',
                            ])
                            ->default(60)
                            ->required(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['inactive_days'] ?? null)) {
                            return $query;
                        }

                        $lastVisitCutoff = now()->subDays((int) $data['inactive_days']);

                        return $query
                            ->whereHas('invoices', fn (Builder $invoices): Builder => $invoices
                                ->where('status', 'posted')
                                ->whereNotNull('issued_at'))
                            ->whereDoesntHave('invoices', fn (Builder $invoices): Builder => $invoices
                                ->where('status', 'posted')
                                ->where('issued_at', '>=', $lastVisitCutoff));
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('contactForReturn')
                        ->label('دعوة للعودة عبر واتساب')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->url(function (Customer $record): ?string {
                            $phone = WhatsAppPhoneNumber::resolve(
                                $record->phone,
                                (string) SystemConfig::get('sys_country_code', ''),
                            );

                            if (! $phone) {
                                return null;
                            }

                            $shopName = (string) SystemConfig::get('sys_shop_name', 'الصالون');
                            $message = "مرحباً {$record->name}، نشتاق لزيارتك في {$shopName}. يسعدنا مساعدتك في حجز موعدك القادم.";

                            return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
                        })
                        ->urlInNewTab()
                        ->visible(fn (Customer $record): bool => filled($record->phone)),
                    Action::make('redeem_points')
                        ->label('استبدال نقاط')
                        ->icon('heroicon-o-gift')
                        ->color('warning')
                        ->form([
                            TextInput::make('points_to_redeem')
                                ->label('عدد النقاط المراد استبدالها')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->maxValue(fn ($record) => (float) $record->points)
                                ->helperText(fn ($record) => 'النقاط المتاحة للعميل: '.number_format($record->points, 0)),
                        ])
                        ->action(function ($record, array $data): void {
                            $pointsToRedeem = (float) $data['points_to_redeem'];
                            if ($pointsToRedeem > (float) $record->points) {
                                Notification::make()
                                    ->title('فشل العملية')
                                    ->body('عدد النقاط المدخل أكبر من نقاط العميل المتاحة.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $rate = (float) SystemConfig::get('sys_customer_points_redeem_ratio', 10);
                            if ($rate <= 0) {
                                $rate = 10;
                            }

                            $convertiblePoints = floor($pointsToRedeem / $rate) * $rate;

                            if ($convertiblePoints <= 0) {
                                Notification::make()
                                    ->title('فشل العملية')
                                    ->body("يجب استبدال عدد نقاط من مضاعفات الـ {$rate} (بحد أدنى {$rate} نقطة).")
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $moneyToAdd = round($convertiblePoints / $rate, 2);

                            DB::transaction(function () use ($record, $convertiblePoints, $moneyToAdd): void {
                                $balanceBefore = (float) $record->balance;
                                $balanceAfter = $balanceBefore + $moneyToAdd;

                                $record->points = (float) $record->points - $convertiblePoints;
                                $record->balance = $balanceAfter;
                                $record->save();

                                CustomerCredit::create([
                                    'branch_id' => session('active_branch_id'),
                                    'customer_id' => $record->id,
                                    'user_id' => auth()->id() ?? User::first()?->id,
                                    'type' => 'redeem',
                                    'amount' => $moneyToAdd,
                                    'balance_before' => $balanceBefore,
                                    'balance_after' => $balanceAfter,
                                    'description' => 'استبدال نقاط ولاء ('.$convertiblePoints.' نقطة) بواسطة الإدارة',
                                ]);
                            });

                            Notification::make()
                                ->title('نجاح العملية')
                                ->body("تم تحويل {$convertiblePoints} نقطة بنجاح إلى رصيد بقيمة {$moneyToAdd} جنيه!")
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => (float) $record->points > 0),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
