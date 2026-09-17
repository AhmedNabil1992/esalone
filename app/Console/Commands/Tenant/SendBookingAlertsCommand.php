<?php

namespace App\Console\Commands\Tenant;

use App\Filament\Resources\Tenant\Reservations\ReservationsResource;
use App\Models\Tenant\Reservation;
use App\Models\Tenant\SystemConfig;
use App\Models\Tenant\User;
use App\Support\Tenant\TenantClock;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use WallaceMartinss\FilamentEvolution\Enums\StatusConnectionEnum;
use WallaceMartinss\FilamentEvolution\Models\WhatsappInstance;
use WallaceMartinss\FilamentEvolution\Services\EvolutionClient;

class SendBookingAlertsCommand extends Command
{
    protected $signature = 'tenants:send-booking-alerts';

    protected $description = 'إرسال تنبيهات تلقائية للإدارة والعملاء باقتراب موعد الحجز';

    public function handle(): int
    {
        if (! tenant()?->isReadyForScheduledTasks()) {
            return self::SUCCESS;
        }

        // 1. Get the configured alert lead time (in minutes)
        $alertLeadTime = (int) SystemConfig::get('sys_booking_alert_time', '30');
        if ($alertLeadTime <= 0) {
            $alertLeadTime = 30;
        }

        $clock = app(TenantClock::class);
        $now = $clock->nowUtc();
        $targetTime = $now->addMinutes($alertLeadTime);

        // 2. Find active reservations starting between now and targetTime that haven't been fully alerted
        $reservations = Reservation::query()
            ->with(['customer', 'device'])
            ->where('status', 'active')
            ->where(function ($query) {
                $query->where('whatsapp_alert_sent', false)
                    ->orWhere('admin_alert_sent', false);
            })
            ->whereBetween('start_time', [$now->subMinutes(5), $targetTime])
            ->get();

        if ($reservations->isEmpty()) {
            return self::SUCCESS;
        }

        // 3. Prepare admin notifications
        $users = User::all();

        // 4. Check WhatsApp service availability
        $whatsappEnabled = (bool) SystemConfig::get('sys_whatsapp_enabled', '0');
        $instance = $whatsappEnabled
            ? WhatsappInstance::query()->where('status', StatusConnectionEnum::OPEN)->first()
            : null;

        $countryCode = preg_replace('/\D/', '', (string) SystemConfig::get('sys_country_code', '')) ?? '';
        $shopName = SystemConfig::get('sys_shop_name', 'gameOver');
        $evolutionClient = app(EvolutionClient::class);

        $sentWhatsAppCount = 0;
        $sentAdminCount = 0;

        foreach ($reservations as $reservation) {
            $customer = $reservation->customer;
            $customerName = $customer->name ?? 'عميل';
            $deviceName = $reservation->device->name ?? 'جهاز اللعب';
            $startTimeFormatted = $clock->utcToLocal($reservation->start_time)->format('h:i A');
            $minutesLeft = max(0, (int) $now->diffInMinutes($reservation->start_time, false));

            // A) Send Admin Notification (Filament Database & Browser Notification)
            if (! $reservation->admin_alert_sent && $users->isNotEmpty()) {
                Notification::make()
                    ->title('📅 تنبيه اقتراب موعد حجز')
                    ->body("العميل *{$customerName}* لديه حجز على جهاز *{$deviceName}* الساعة {$startTimeFormatted} (بعد {$minutesLeft} دقيقة).")
                    ->icon('heroicon-o-calendar')
                    ->info()
                    ->actions([
                        Action::make('view')
                            ->label('عرض الحجوزات')
                            ->url(ReservationsResource::getUrl('index', panel: 'admin', isAbsolute: false)),
                    ])
                    ->sendToDatabase($users, isEventDispatched: true);

                $reservation->update(['admin_alert_sent' => true]);
                $sentAdminCount++;
                $this->line("✓ تم إرسال إشعار حجز للإدارة: {$customerName} ({$deviceName})");
            }

            // B) Send Customer WhatsApp Notification
            if (! $reservation->whatsapp_alert_sent && $whatsappEnabled && $instance && $customer && $customer->phone) {
                $phone = preg_replace('/\D/', '', (string) $customer->phone) ?? '';
                if (str_starts_with($phone, '00')) {
                    $phone = substr($phone, 2);
                }
                if (! empty($phone)) {
                    $resolvedCode = $countryCode;
                    if ($resolvedCode === '2' && (str_starts_with($phone, '01') || str_starts_with($phone, '1'))) {
                        $resolvedCode = '20';
                    }

                    if ($resolvedCode && ! str_starts_with($phone, $resolvedCode)) {
                        if (str_starts_with($phone, '0')) {
                            $phone = substr($phone, 1);
                        }
                        $phone = $resolvedCode.$phone;
                    }

                    $message = "مرحباً يا *{$customer->name}*! 👋\n\n";
                    $message .= "نود تذكيرك باقتراب موعد حجزك في *{$shopName}*:\n";
                    $message .= "🎮 *المكان:* {$deviceName}\n";
                    $message .= "⏰ *الساعة:* {$startTimeFormatted} (بعد {$minutesLeft} دقيقة)\n\n";
                    $message .= 'يرجى الحضور في الموعد المحدد. يسعدنا استقبالكم دائماً! ❤️';

                    try {
                        $evolutionClient->sendText($instance->name, $phone, $message);
                        $reservation->update(['whatsapp_alert_sent' => true]);
                        $sentWhatsAppCount++;
                        $this->line("✓ تم إرسال تنبيه واتساب لـ {$customer->name} على {$phone}");
                    } catch (\Exception $e) {
                        Log::error("Failed to send booking alert to reservation #{$reservation->id}: ".$e->getMessage());
                    }
                }
            }
        }

        if ($sentAdminCount > 0 || $sentWhatsAppCount > 0) {
            $this->info("تم إرسال {$sentAdminCount} إشعار حجز للإدارة و {$sentWhatsAppCount} تنبيه واتساب بنجاح.");
        }

        return self::SUCCESS;
    }
}
