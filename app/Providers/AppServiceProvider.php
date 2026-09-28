<?php

namespace App\Providers;

use App\Livewire\QuickNavigation;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        $this->loadViewsFrom(resource_path('views'), 'support');
        $this->loadTranslationsFrom(resource_path('lang'), 'support');
        if (file_exists(base_path('lang/vendor/filament-footer'))) {
            $this->loadTranslationsFrom(base_path('lang/vendor/filament-footer'), 'filament-footer');
        }
        Livewire::component('quick-navigation', QuickNavigation::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        $httpVerify = config('services.gemini.http_verify', true);
        if (is_string($httpVerify)) {
            $httpVerify = filter_var($httpVerify, FILTER_VALIDATE_BOOLEAN);
        }

        if (! $httpVerify) {
            Http::globalOptions(['verify' => false]);
        }

        RateLimiter::for('tenant-provisioning', function (Request $request): Limit {
            $identity = hash('sha256', (string) $request->bearerToken().'|'.$request->ip());

            return Limit::perMinute(60)
                ->by($identity)
                ->response(fn (Request $request, array $headers) => response()->json([
                    'error_code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Too many tenant API requests. Retry later.',
                    'errors' => [],
                ], 429, $headers));
        });

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): Password => Password::min(8));
    }
}
