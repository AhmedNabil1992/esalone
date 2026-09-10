<?php

declare(strict_types=1);

namespace App\Filament\Customer\Pages;

use App\Actions\Tenant\RedeemCustomerPointsAction;
use App\Models\Tenant\Customer;
use App\Models\Tenant\SystemConfig;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PointsRedemption extends Page
{
    protected static ?string $title = 'استبدال نقاط الولاء';

    protected static ?string $navigationLabel = 'استبدال النقاط';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.customer.pages.points-redemption';

    public float $pointsPerCurrency = 10.0;

    public function mount(): void
    {
        $rate = (float) SystemConfig::get('sys_customer_points_redeem_ratio', '10');
        $this->pointsPerCurrency = is_finite($rate) && $rate > 0 ? $rate : 10.0;
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

                    try {
                        $credit = app(RedeemCustomerPointsAction::class)->execute((int) $customer->id, $pointsToRedeem);
                    } catch (ValidationException $exception) {
                        throw ValidationException::withMessages([
                            'mountedActions.0.data.points_to_redeem' => $exception->errors()['points'] ?? $exception->getMessage(),
                        ]);
                    }
                    $moneyToAdd = (float) $credit->amount;

                    Notification::make()
                        ->title('تم استبدال النقاط وإضافة الرصيد بنجاح!')
                        ->body('تمت إضافة '.number_format($moneyToAdd, 2).' ج.م إلى رصيدك.')
                        ->success()
                        ->send();

                    $this->redirect(static::getUrl());
                }),
        ];
    }
}
