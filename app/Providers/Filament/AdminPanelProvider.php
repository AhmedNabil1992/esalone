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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Throwable;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
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
            // ->navigationGroups([
            //     NavigationGroup::make('System')
            //         ->label('System')
            //         ->icon('heroicon-o-cog'),
            //     NavigationGroup::make('User Management')
            //         ->label('User Management')
            //         ->icon('heroicon-o-users'),
            // ])
            // ->navigationItems([
            //     NavigationItem::make('Dashboard')
            //         ->icon('heroicon-o-home')
            //         ->url('/admin')
            //         ->group('System'),
            //     NavigationItem::make('Users')
            //         ->icon('heroicon-o-users')
            //         ->url('/admin/users')
            //         ->group('User Management')
            //         ->sort(1),
            //     NavigationItem::make('Roles')
            //         ->icon('heroicon-o-shield-check')
            //         ->url('/admin/shield/roles')
            //         ->group('User Management')
            //         ->sort(2),
            // ])
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
            ])
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
            ]);
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
}
