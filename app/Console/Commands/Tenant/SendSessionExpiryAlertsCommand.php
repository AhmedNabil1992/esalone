<?php

namespace App\Console\Commands\Tenant;

use App\Models\Tenant\PlaySessions;
use App\Models\Tenant\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class SendSessionExpiryAlertsCommand extends Command
{
    protected $signature = 'tenants:send-session-expiry-alerts';

    protected $description = 'إرسال تنبيهات تلقائية لإدارة المكان بقرب انتهاء وقت الجلسات على الأجهزة (قبل 5 دقائق)';

    public function handle(): int
    {
        $activeSessions = PlaySessions::query()
            ->with(['device', 'latestDetail'])
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('alert_5min_sent', false)
                    ->orWhere('alert_ended_sent', false);
            })
            ->get();

        if ($activeSessions->isEmpty()) {
            return self::SUCCESS;
        }

        $users = User::all();

        if ($users->isEmpty()) {
            return self::SUCCESS;
        }

        $sentCount = 0;

        foreach ($activeSessions as $session) {
            $latestDetail = $session->latestDetail;
            if (! $latestDetail || ! $latestDetail->end_time) {
                continue;
            }

            $endTime = $latestDetail->end_time;
            $deviceName = $session->device?->name ?? 'جهاز اللعب';

            $secondsLeft = $endTime->timestamp - now()->timestamp;

            // Check if session time has ended
            if ($secondsLeft <= 0 || $endTime->isPast()) {
                if (! $session->alert_ended_sent) {
                    Notification::make()
                        ->title('🔴 انتهى الوقت المحدد')
                        ->body("جهاز *{$deviceName}* انتهى الوقت المحدد للجلسة الخاصة به!")
                        ->icon('heroicon-o-x-circle')
                        ->danger()
                        ->actions([
                            Action::make('view')
                                ->label('التحكم بالأجهزة')
                                ->url(route('filament.admin.pages.device-control')),
                        ])
                        ->sendToDatabase($users);

                    $session->update([
                        'alert_ended_sent' => true,
                        'alert_5min_sent' => true,
                    ]);
                    $sentCount++;

                    $this->line("✓ تم إرسال إشعار انتهاء الوقت للجهاز: {$deviceName}");
                }

                continue;
            }

            // Calculate remaining time for 5-minute warning (300 seconds)
            if ($secondsLeft <= 300 && $secondsLeft > 0 && ! $session->alert_5min_sent) {
                $minutesLeft = max(1, (int) ceil($secondsLeft / 60));
                $bodyMsg = $secondsLeft <= 60
                    ? "جهاز *{$deviceName}* متبقي عليه أقل من دقيقة على انتهاء الوقت!"
                    : "جهاز *{$deviceName}* متبقي عليه {$minutesLeft} دقائق على انتهاء الوقت.";

                Notification::make()
                    ->title('⏰ تنبيه قرب انتهاء الوقت')
                    ->body($bodyMsg)
                    ->icon('heroicon-o-clock')
                    ->warning()
                    ->actions([
                        Action::make('view')
                            ->label('التحكم بالأجهزة')
                            ->url(route('filament.admin.pages.device-control')),
                    ])
                    ->sendToDatabase($users);

                $session->update(['alert_5min_sent' => true]);
                $sentCount++;

                $this->line("✓ تم إرسال تنبيه قرب انتهاء الوقت للجهاز: {$deviceName}");
            }
        }

        if ($sentCount > 0) {
            $this->info("تم إرسال {$sentCount} تنبيه انتهاء وقت جلسة بنجاح.");
        }

        return self::SUCCESS;
    }
}
