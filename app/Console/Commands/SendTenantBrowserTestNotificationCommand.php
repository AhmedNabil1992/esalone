<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use Emuniq\FilamentBrowserNotifications\Jobs\SendDatabaseNotificationWebPush;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Facades\Tenancy;
use Throwable;

class SendTenantBrowserTestNotificationCommand extends Command
{
    protected $signature = 'tenants:browser-notifications:test
                            {user=1 : User ID داخل كل Tenant DB}
                            {--sync : إرسال فوري بدل queue}
                            {--only-active : إرسال للتينانتس المفعلة فقط}';

    protected $description = 'Send browser test push to one tenant user across all tenant databases.';

    public function handle(): int
    {
        $targetUserId = (int) $this->argument('user');
        $sendSync = (bool) $this->option('sync');

        $tenantsQuery = Tenant::query()->orderBy('id');
        if ((bool) $this->option('only-active')) {
            $tenantsQuery->where('is_active', true);
        }

        $tenants = $tenantsQuery->get();
        if ($tenants->isEmpty()) {
            $this->warn('لا يوجد tenants لإرسال الإشعار.');

            return self::SUCCESS;
        }

        $this->info('سيتم الإرسال إلى المستخدم #'.$targetUserId.' عبر '.$tenants->count().' Tenant(s).');

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($tenants as $tenant) {
            $tenantKey = (string) $tenant->getTenantKey();

            try {
                Tenancy::initialize($tenant);

                $userModel = config('auth.providers.users.model', TenantUser::class);
                /** @var Model|null $user */
                $user = $userModel::query()->find($targetUserId);

                if (! $user) {
                    $skipped++;
                    $this->line("[tenant:{$tenantKey}] تخطي: المستخدم غير موجود.");

                    continue;
                }

                if (! method_exists($user, 'pushSubscriptions')) {
                    $skipped++;
                    $this->line("[tenant:{$tenantKey}] تخطي: الموديل لا يدعم pushSubscriptions (أضف HasPushSubscriptions). ");

                    continue;
                }

                $subscriptionCount = $user->pushSubscriptions()->count();
                if ($subscriptionCount === 0) {
                    $skipped++;
                    $this->line("[tenant:{$tenantKey}] تخطي: المستخدم بدون اشتراكات Push.");

                    continue;
                }

                $job = new SendDatabaseNotificationWebPush(
                    $user,
                    'اختبار إشعار المتصفح',
                    'تم إرسال إشعار تجريبي من مستوى جميع التينانتس.',
                    '/admin/profile',
                );

                if ($sendSync) {
                    dispatch_sync($job);
                } else {
                    dispatch($job);
                }

                $sent++;
                $this->line("[tenant:{$tenantKey}] تم الإرسال للمستخدم #{$targetUserId} ({$subscriptionCount} جهاز). ");
            } catch (Throwable $throwable) {
                $failed++;
                $this->error("[tenant:{$tenantKey}] فشل: {$throwable->getMessage()}");
            } finally {
                Tenancy::end();
            }
        }

        $this->newLine();
        $this->info("انتهى التنفيذ: sent={$sent}, skipped={$skipped}, failed={$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
