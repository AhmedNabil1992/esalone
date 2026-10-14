<?php

namespace App\Console\Commands\Tenant;

use App\Models\Tenant\Appointment;
use App\Models\Tenant\SystemConfig;
use App\Support\Tenant\TenantClock;
use App\Support\Tenant\WhatsAppPhoneNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;
use WallaceMartinss\FilamentEvolution\Enums\StatusConnectionEnum;
use WallaceMartinss\FilamentEvolution\Models\WhatsappInstance;
use WallaceMartinss\FilamentEvolution\Services\EvolutionClient;

class SendAppointmentRemindersCommand extends Command
{
    protected $signature = 'tenants:send-appointment-reminders';

    protected $description = 'إرسال تذكيرات واتساب بمواعيد الصالون المؤكدة';

    public function handle(): int
    {
        if (! tenant()?->isReadyForScheduledTasks()) {
            return self::SUCCESS;
        }

        $whatsappEnabled = (bool) SystemConfig::get('sys_whatsapp_enabled', '0');
        $instance = $whatsappEnabled
            ? WhatsappInstance::query()->where('status', StatusConnectionEnum::OPEN)->first()
            : null;

        if (! $whatsappEnabled || ! $instance) {
            return self::SUCCESS;
        }

        $alertLeadTime = (int) SystemConfig::get('sys_booking_alert_time', '30');
        $alertLeadTime = $alertLeadTime > 0 ? $alertLeadTime : 30;
        $clock = app(TenantClock::class);
        $now = $clock->nowUtc();
        $targetTime = $now->copy()->addMinutes($alertLeadTime);
        $countryCode = (string) SystemConfig::get('sys_country_code', '');
        $shopName = (string) SystemConfig::get('sys_shop_name', 'الصالون');
        $evolutionClient = app(EvolutionClient::class);
        $sentCount = 0;
        $failedCount = 0;

        $appointments = Appointment::query()
            ->with(['customer', 'staffMember', 'items'])
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereNull('reminder_sent_at')
            ->whereBetween('start_at', [$now->copy()->subMinutes(5), $targetTime])
            ->orderBy('start_at')
            ->get();

        foreach ($appointments as $appointment) {
            $phone = WhatsAppPhoneNumber::resolve(
                $appointment->contact_phone ?? $appointment->customer?->phone,
                $countryCode,
            );

            if ($phone === null) {
                continue;
            }

            $customerName = $appointment->contact_name
                ?: $appointment->customer?->name
                ?: 'عميلنا العزيز';
            $startTime = $clock->utcToLocal($appointment->start_at)->format('d/m/Y h:i A');
            $serviceNames = $appointment->items->pluck('service_name')->implode('، ');
            $message = "أهلاً {$customerName}، نذكّرك بموعدك في *{$shopName}* يوم {$startTime}.";

            if ($serviceNames !== '') {
                $message .= "\nالخدمات: {$serviceNames}.";
            }

            if ($appointment->staffMember) {
                $message .= "\nالموظف: {$appointment->staffMember->name}.";
            }

            $message .= "\nنسعد بزيارتك، وإذا احتجت إلى تعديل الموعد يرجى التواصل مع الصالون.";

            try {
                $evolutionClient->sendText($instance->name, $phone, $message);
                $appointment->forceFill(['reminder_sent_at' => now()])->save();
                $sentCount++;
            } catch (Throwable $exception) {
                Log::error('Failed to send salon appointment reminder.', [
                    'appointment_id' => $appointment->getKey(),
                    'error' => $exception->getMessage(),
                ]);
                $failedCount++;
            }
        }

        if ($sentCount > 0) {
            $this->info("تم إرسال {$sentCount} تذكير موعد.");
        }

        if ($failedCount > 0) {
            $this->error("تعذر إرسال {$failedCount} تذكير موعد.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
