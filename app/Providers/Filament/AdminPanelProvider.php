<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Tenant\ExpenseHistories\ExpenseHistoryResource;
use App\Filament\Widgets\AdminStatsOverview;
use App\Filament\Widgets\CurrentCashBox;
use App\Filament\Widgets\ExpensesTypeSum;
use App\Filament\Widgets\LowStockAlertsWidget;
use App\Filament\Widgets\PurchaseOverviewWidget;
use App\Filament\Widgets\SalesChartWidget;
use App\Http\Middleware\CheckTenantSubscriptionExpiry;
use App\Http\Middleware\EnsureTenantHasOpenShift;
use App\Http\Middleware\SetTenantTimezone;
use App\Models\Tenant\Shift;
use App\Models\Tenant\SystemConfig as Setting;
use App\Tenancy\TenantBackgroundsProvider;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
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
use lockscreen\FilamentLockscreen\Lockscreen;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Swis\Filament\Backgrounds\FilamentBackgroundsPlugin;
use Throwable;

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
            ->brandName(fn (): string => $this->resolveTenantBrandName()) // سيب دي عشان الـ Title بتاع المتصفح
            ->favicon(asset('apple-touch-icon.png'))
            ->brandLogo(fn (): HtmlString => new HtmlString('
                <div style="display: flex; align-items: center; gap: 12px; white-space: nowrap;">
                    <img src="'.asset('images/favicon.svg').'" style="height: 2.5rem; width: auto; flex-shrink: 0;" alt="Logo">
                    <span style="font-size: 1.25rem; font-weight: bold; line-height: 1;" class="text-gray-950 dark:text-white">
                        '.$this->resolveTenantBrandName().'
                    </span>
                </div>
            '))
            ->brandLogoHeight('2.5rem')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->unsavedChangesAlerts()
            ->topNavigation()
            ->maxContentWidth(Width::Full)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverResources(in: app_path('Filament/Resources/Tenant'), for: 'App\Filament\Resources\Tenant')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AdminStatsOverview::class,
                CurrentCashBox::class,
                ExpensesTypeSum::class,
                PurchaseOverviewWidget::class,
                SalesChartWidget::class,
                LowStockAlertsWidget::class,
            ])
            ->navigationGroups([
                // 1. العمليات اليومية
                NavigationGroup::make()
                    ->label('اللعب')
                    ->icon('heroicon-o-play'),

                NavigationGroup::make()
                    ->label('الشيفتات')
                    ->icon('heroicon-o-clock'),

                NavigationGroup::make()
                    ->label('العملاء')
                    ->icon('heroicon-o-user-group'),

                // 2. الحسابات والمخازن
                NavigationGroup::make()
                    ->label('الإدارة المالية')
                    ->icon('heroicon-o-banknotes'),

                NavigationGroup::make()
                    ->label('إدارة المخزون')
                    ->icon('heroicon-o-archive-box'),

                NavigationGroup::make()
                    ->label('المشتريات')
                    ->icon('heroicon-o-shopping-cart'),

                // 3. التقارير
                NavigationGroup::make()
                    ->label('التقارير')
                    ->icon('heroicon-o-chart-pie'),
                NavigationGroup::make()
                    ->label('الإحصائيات')
                    ->icon('heroicon-o-presentation-chart-line'),
                NavigationGroup::make()
                    ->label('تقارير إدارية')
                    ->icon('heroicon-o-document-chart-bar'),

                // 4. الإعدادات (في الأسفل)
                NavigationGroup::make()
                    ->label('إدارة الأجهزة')
                    ->icon('heroicon-o-tv'),

                NavigationGroup::make()
                    ->label('إعدادات المستخدمين')
                    ->icon('heroicon-o-users'),

                NavigationGroup::make()
                    ->label('إعدادات النظام')
                    ->icon('heroicon-o-cog-6-tooth'),
            ])
            ->navigationItems([
                NavigationItem::make('تسجيل مصروف')
                    ->url(fn (): string => route('filament.admin.resources.tenant.expense-histories.create'))
                    ->icon('heroicon-o-plus-circle')
                    ->group('الإدارة المالية')
                    ->sort(1)
                    ->visible(fn (): bool => ExpenseHistoryResource::canCreate()),
                NavigationItem::make('تقفيل الشيفت')
                    ->url(function (): string {
                        $activeShift = Shift::currentOpenShift();

                        return $activeShift
                            ? route('filament.admin.resources.tenant.shifts.shifts.close', ['record' => $activeShift])
                            : '#';
                    })
                    ->icon('heroicon-o-banknotes')
                    ->group('الشيفتات')
                    ->sort(2)
                    ->visible(fn (): bool => Shift::query()->open()->active()->exists()),
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup('إعدادات المستخدمين')
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
                Lockscreen::make()
                    // ->usingCustomTableColumns('email','password') // Use custom table columns. Default:  email, password.
                    ->enableRateLimit() // Enable rate limit for the lockscreen. Default: Enable, 5 attempts in 1 minute.
                    // ->setUrl() // Customize the lockscreen url.
                    ->enableIdleTimeout() // Enable auto lock during idle time. Default: Enable, 30 minutes.
                    ->disableDisplayName() // Display the name of the user based on the attribute supplied. Default: name
                    // ->icon() // Customize the icon of the lockscreen.
                    ->enablePlugin(), // Enable the plugin.
                FilamentBackgroundsPlugin::make()
                // ->showAttribution(false)
                // ->imageProvider(
                //     TenantBackgroundsProvider::make()
                // )
                ,

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
                SetTenantTimezone::class,
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
            ->databaseNotifications()
            ->topNavigation();
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
