<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant\Shift;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantHasOpenShift
{
    /**
     * @var array<int, string>
     */
    protected array $routesRequiringOpenShift = [
        'filament.admin.resources.tenant.purchases.create',
        'filament.admin.resources.tenant.financial-transactions.create',
        'filament.admin.resources.tenant.expense-histories.create',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $routeName = (string) ($request->route()?->getName() ?? '');

        if (! in_array($routeName, $this->routesRequiringOpenShift, true)) {
            return $next($request);
        }

        if (! Schema::connection('tenant')->hasTable('shifts')) {
            return $next($request);
        }

        if (Shift::currentOpenShift()) {
            return $next($request);
        }

        if ($request->isMethod('get')) {
            $quickOpenUrl = url('/tenant/shifts/quick-open').'?'.http_build_query([
                'redirect' => $request->fullUrl(),
            ]);
            $cancelUrl = url('/admin');

            Notification::make()
                ->title('لا يوجد شيفت مفتوح')
                ->body('لا يمكن متابعة هذه العملية بدون شيفت مفتوح. هل تريد بدء شيفت جديد الآن؟')
                ->warning()
                ->persistent()
                ->actions([
                    Action::make('confirmOpenShift')
                        ->label('موافق')
                        ->button()
                        ->color('primary')
                        ->url($quickOpenUrl),
                    Action::make('cancelOpenShift')
                        ->label('إلغاء')
                        ->color('gray')
                        ->url($cancelUrl),
                ])
                ->send();

            return redirect()->to($cancelUrl);
        }

        return $next($request);
    }
}
