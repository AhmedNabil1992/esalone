<?php

namespace App\Console\Commands\Tenant;

use App\Models\Tenant\Reservation;
use App\Models\Tenant\SystemConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use WallaceMartinss\FilamentEvolution\Services\EvolutionClient;
use WallaceMartinss\FilamentEvolution\Services\WhatsappService;

class SendBookingAlertsCommand extends Command
{
    protected $signature = 'tenants:send-booking-alerts';

    protected $description = 'إرسال تنبيهات تلقائية للعملاء عبر الواتساب باقتراب موعد حجزهم';

    public function handle(): int
    {
        
        // 1. Check if WhatsApp is enabled
        $whatsappEnabled = (bool) SystemConfig::get('sys_whatsapp_enabled', '0');
        if (! $whatsappEnabled) {
            $this->info('الواتساب غير مفعل في إعدادات النظام.');

            return self::SUCCESS;
        }

        // 2. Get the configured alert lead time (in minutes)
        $alertLeadTime = (int) SystemConfig::get('sys_booking_alert_time', '30');
        if ($alertLeadTime <= 0) {
            $this->info('وقت التنبيه غير صالح أو غير معرّف.');

            return self::SUCCESS;
        }

        $now = now();
        $targetTime = now()->addMinutes($alertLeadTime);

        // 3. Find active reservations starting between now and targetTime that haven't been alerted
        $reservations = Reservation::query()
            ->with(['customer', 'device'])
            ->where('status', 'active')
            ->where('whatsapp_alert_sent', false)
            ->whereBetween('start_time', [$now, $targetTime])
            ->get();

        if ($reservations->isEmpty()) {
            $this->info('لا توجد حجوزات قريبة تتطلب إرسال تنبيهات.');

            return self::SUCCESS;
        }

        // 4. Get first open WhatsApp instance
        $whatsappService = app(WhatsappService::class);
        $instance = $whatsappService->getConnectedInstances()->first();
        if (! $instance) {
            $this->error('لا توجد أي جلسة واتساب متصلة حالياً لإرسال التنبيهات.');

            return self::FAILURE;
        }

        $countryCode = preg_replace('/\D/', '', (string) SystemConfig::get('sys_country_code', ''));
        $shopName = SystemConfig::get('sys_shop_name', 'gameOver');
        $sentCount = 0;

        // Use EvolutionClient directly to bypass the package's broken formatNumber method
        $evolutionClient = app(EvolutionClient::class);

        foreach ($reservations as $reservation) {
            $customer = $reservation->customer;
            if (! $customer || ! $customer->phone) {
                continue;
            }

            // Format phone number (same logic as SendInvoiceWhatsAppAction)
            $phone = preg_replace('/\D/', '', $customer->phone);
            if (str_starts_with($phone, '00')) {
                $phone = substr($phone, 2);
            }
            if (empty($phone)) {
                continue;
            }

            // Smart fix for Egyptian country code (2 → 20)
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

            // Construct reservation alert message
            $startTimeFormatted = $reservation->start_time->format('h:i A');
            $minutesLeft = max(1, (int) now()->diffInMinutes($reservation->start_time));
            $deviceName = $reservation->device?->name ?? 'جهاز اللعب';

            $message = "مرحباً يا *{$customer->name}*! 👋\n\n";
            $message .= "نود تذكيرك باقتراب موعد حجزك في *{$shopName}*:\n";
            $message .= "🎮 *المكان:* {$deviceName}\n";
            $message .= "⏰ *الساعة:* {$startTimeFormatted} (بعد {$minutesLeft} دقيقة)\n\n";
            $message .= 'يرجى الحضور في الموعد المحدد. يسعدنا استقبالكم دائماً! ❤️';

            try {
                $evolutionClient->sendText($instance->name, $phone, $message);
                $reservation->update(['whatsapp_alert_sent' => true]);
                $sentCount++;
                $this->line("✓ تم إرسال تنبيه لـ {$customer->name} على {$phone}");
            } catch (\Exception $e) {
                Log::error("Failed to send booking alert to reservation #{$reservation->id}: ".$e->getMessage());
                $this->error("✗ فشل الإرسال للحجز #{$reservation->id}: ".$e->getMessage());
            }
        }

        $this->info("تم إرسال {$sentCount} تنبيه حجز بنجاح.");

        return self::SUCCESS;
    }
}
