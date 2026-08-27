<?php

declare(strict_types=1);

namespace App\Filament\Customer\Pages;

use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerCredit;
use App\Models\Tenant\SystemConfig;
use App\Models\Tenant\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PointsRedemption extends Page
{
    protected static ?string $title = 'استبدال نقاط الولاء';

    protected static ?string $navigationLabel = 'استبدال النقاط';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.customer.pages.points-redemption';

    public float $pointsPerCurrency = 1.0;

    public function mount(): void
    {
        $rate = (float) SystemConfig::get('sys_customer_points_redeem_ratio', '1');
        $this->pointsPerCurrency = $rate > 0 ? $rate : 1.0;
    }

    public function getCustomerProperty(): ?Customer
    {
        return Auth::guard('customer')->user();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('redeem')
                ->label('تحويل النقاط إلى رصيد')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->form([
                    TextInput::make('points_to_redeem')
                        ->label('عدد النقاط المراد استبدالها')
                        ->numeric()
                        ->required()
                        ->live(onBlur: false)
                        ->minValue(1)
                        ->maxValue(fn (): float => (float) ($this->customer?->points ?? 0))
                        ->helperText(fn ($get): string => 'النقاط المحددة تعادل رصيد قدره: '.number_format(floor(((float) $get('points_to_redeem')) / $this->pointsPerCurrency) * $this->pointsPerCurrency / $this->pointsPerCurrency, 2).' ج.م'),
                ])
                ->action(function (array $data): void {
                    $pointsToRedeem = (float) $data['points_to_redeem'];
                    /** @var Customer|null $customer */
                    $customer = Auth::guard('customer')->user();

                    if (! $customer || $pointsToRedeem <= 0) {
                        return;
                    }

                    if ((float) $customer->points < $pointsToRedeem) {
                        Notification::make()
                            ->title('عفواً، رصيد النقاط غير كافٍ')
                            ->danger()
                            ->send();

                        return;
                    }

                    $rate = (float) SystemConfig::get('sys_customer_points_redeem_ratio', '1');
                    if ($rate <= 0) {
                        $rate = 1.0;
                    }

                    $convertiblePoints = floor($pointsToRedeem / $rate) * $rate;
                    if ($convertiblePoints <= 0) {
                        Notification::make()
                            ->title('عدد النقاط غير كافٍ للتحويل بناءً على معدل التحويل الحالي')
                            ->warning()
                            ->send();

                        return;
                    }

                    $moneyToAdd = round($convertiblePoints / $rate, 2);

                    DB::transaction(function () use ($customer, $convertiblePoints, $moneyToAdd): void {
                        $balanceBefore = (float) $customer->balance;
                        $balanceAfter = $balanceBefore + $moneyToAdd;

                        $customer->points = (float) $customer->points - $convertiblePoints;
                        $customer->balance = $balanceAfter;
                        $customer->save();

                        CustomerCredit::query()->create([
                            'customer_id' => $customer->id,
                            'branch_id' => session('active_branch_id') && session('active_branch_id') !== 'all' ? (int) session('active_branch_id') : 1,
                            'user_id' => User::query()->first()?->id ?? 1,
                            'type' => 'redeem',
                            'amount' => $moneyToAdd,
                            'balance_before' => $balanceBefore,
                            'balance_after' => $balanceAfter,
                            'description' => "تحويل نقاط الولاء إلى رصيد ({$convertiblePoints} نقطة) عبر البوابة الإلكترونية",
                        ]);
                    });

                    Notification::make()
                        ->title('تم استبدال النقاط وإضافة الرصيد بنجاح!')
                        ->body("تم تحويل {$convertiblePoints} نقطة إلى ".number_format($moneyToAdd, 2).' ج.م رصيد مالي.')
                        ->success()
                        ->send();

                    $this->redirect(static::getUrl());
                }),
        ];
    }
}
