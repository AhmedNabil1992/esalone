<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\PurchaseOverviewWidget;
use App\Http\Middleware\CheckTenantSubscriptionExpiry;
use App\Http\Middleware\EnsureTenantHasOpenShift;
use App\Models\Tenant\SystemConfig as Setting;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Schemas\Schema as FilamentSchema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Throwable;
use Filament\Navigation\NavigationGroup;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        $this->configureBroadcasting();

        FilamentSchema::configureUsing(function (FilamentSchema $schema): void {
            $schema->defaultCurrency(fn (): string => $this->resolveTenantCurrency());
        });

        Table::configureUsing(function (Table $table): void {
            $table->defaultCurrency(fn (): string => $this->resolveTenantCurrency());
        });
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile()
            ->brandName(fn (): string => $this->resolveTenantBrandName())
            ->colors([
                'primary' => Color::Blue,
            ])
            ->unsavedChangesAlerts()
            // ->topNavigation()
            ->sidebarFullyCollapsibleOnDesktop(true)
            ->maxContentWidth(Width::Full)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverResources(in: app_path('Filament/Resources/Tenant'), for: 'App\Filament\Resources\Tenant')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                PurchaseOverviewWidget::class,
            ])
            ->navigationGroups([
                // 1. العمليات اليومية
                NavigationGroup::make('Play')
                    ->label('اللعب')
                    ->icon('heroicon-o-play'),
                    
                NavigationGroup::make('Shifts')
                    ->label('الشيفتات')
                    ->icon('heroicon-o-clock'),

                NavigationGroup::make('Customers')
                    ->label('العملاء')
                    ->icon('heroicon-o-user-group'),

                // 2. الحسابات والمخازن
                NavigationGroup::make('Financial')
                    ->label('الإدارة المالية')
                    ->icon('heroicon-o-banknotes'),

                NavigationGroup::make('Inventory')
                    ->label('إدارة المخزون')
                    ->icon('heroicon-o-archive-box'),

                NavigationGroup::make('Purchases')
                    ->label('المشتريات')
                    ->icon('heroicon-o-shopping-cart'),

                // 3. التقارير
                NavigationGroup::make('Reports')
                    ->label('التقارير')
                    ->icon('heroicon-o-chart-pie'),

                // 4. الإعدادات (في الأسفل)
                NavigationGroup::make('Devices')
                    ->label('إدارة الأجهزة')
                    ->icon('heroicon-o-tv'),

                NavigationGroup::make('Users')
                    ->label('إعدادات المستخدمين')
                    ->icon('heroicon-o-users'),

                NavigationGroup::make('Settings')
                    ->label('إعدادات النظام')
                    ->icon('heroicon-o-cog-6-tooth'),
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup('Users')
                    ->navigationLabel('صلاحيات المستخدمين')
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 2,
                        'xl' => 3,
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 1,
                        'lg' => 2,
                        'xl' => 3,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ]),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn (): HtmlString => new HtmlString(
                    '<meta name="mobile-web-app-capable" content="yes">'
                ),
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): HtmlString => new HtmlString(
                    view('filament.banners.subscription-warning')->render()
                ),
            )
            ->middleware([
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                // CheckTenantSubscriptionExpiry::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureTenantHasOpenShift::class,
            ])
            ->databaseNotifications();
    }

    protected function resolveTenantCurrency(): string
    {
        try {
            if (! Schema::connection('tenant')->hasTable('system_configs')) {
                return 'EGP';
            }

            $currencyCode = strtoupper((string) Setting::get('sys_locale_currency', 'EGP'));

            return blank($currencyCode) ? 'EGP' : $currencyCode;
        } catch (Throwable) {
            return 'EGP';
        }
    }

    protected function resolveTenantBrandName(): string
    {
        try {
            if (! Schema::connection('tenant')->hasTable('system_configs')) {
                return 'PS Web';
            }

            $brandName = trim((string) Setting::get('sys_shop_name', 'PS Web'));

            return blank($brandName) ? 'PS Web' : $brandName;
        } catch (Throwable) {
            return 'PS Web';
        }
    }

    protected function configureBroadcasting(): void
    {
        $isSecure = (string) config('reverb.apps.apps.0.options.scheme', 'https') === 'https';
        $host = (string) config('reverb.apps.apps.0.options.host', 'ps-web.test');
        $port = (int) config('reverb.apps.apps.0.options.port', 8080);
        $key = (string) config('broadcasting.connections.reverb.key', '');

        Config::set('filament.broadcasting.echo.key', $key);
        Config::set('filament.broadcasting.echo.wsHost', $host);
        Config::set('filament.broadcasting.echo.wsPort', $port);
        Config::set('filament.broadcasting.echo.wssPort', $port);
        Config::set('filament.broadcasting.echo.forceTLS', $isSecure);
        Config::set('filament.broadcasting.echo.encrypted', $isSecure);
        Config::set('filament.broadcasting.echo.enabledTransports', ['ws', 'wss']);
    }
}
