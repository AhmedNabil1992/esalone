<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckTenantSubscriptionExpiry
{
    public function handle(Request $request, Closure $next)
    {
        // فقط لـ Admin/Filament
        if (Auth::check() && $this->isAdminPanel($request)) {
            $tenant = tenant();

            Log::debug('Checking subscription expiry', [
                'path' => $request->path(),
                'tenant_id' => $tenant?->id,
                'tenant_ended_at' => $tenant?->ended_at,
            ]);

            if ($tenant && $tenant->ended_at) {
                $daysRemaining = now()->diffInDays($tenant->ended_at, false);

                Log::debug('Days remaining: '.$daysRemaining);

                // إذا كان هناك 3 أيام أو أقل متبقية
                if ($daysRemaining > 0 && $daysRemaining <= 3) {
                    session()->flash('subscription_warning', [
                        'days' => $daysRemaining,
                        'ended_at' => $tenant->ended_at->format('Y-m-d'),
                    ]);

                    Log::info('Subscription warning flashed', [
                        'days' => $daysRemaining,
                        'tenant_id' => $tenant->id,
                    ]);
                }
                // إذا انتهى الاشتراك
                elseif ($daysRemaining <= 0) {
                    session()->flash('subscription_expired', true);

                    Log::info('Subscription expired flashed', [
                        'tenant_id' => $tenant->id,
                    ]);
                }
            }
        }

        return $next($request);
    }

    private function isAdminPanel(Request $request): bool
    {
        // تحقق من أن الطلب في Filament Admin
        // يبحث عن 'admin' في البداية أو بعد '/'
        $path = $request->path();

        return str_starts_with($path, 'admin') || str_contains($path, '/admin');
    }
}
