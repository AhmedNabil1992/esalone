<?php

namespace App\Http\Middleware;

use App\Filament\Resources\Tenant\Branches\BranchResource;
use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantBranchLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenancy()->tenant ?? (function_exists('tenant') ? tenant() : null);

        if ($tenant && ! $tenant->canCreateBranch()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'عفواً، لقد وصلت للحد الأقصى المسموح به من الفروع للباقة الحالية.',
                    'branches_limit' => $tenant->getBranchesLimit(),
                ], 403);
            }

            Notification::make()
                ->title('عفواً، لقد وصلت للحد الأقصى المسموح به من الفروع')
                ->body("الحد الأقصى المسموح به لحسابك هو {$tenant->getBranchesLimit()} فرع. يرجى ترقية الباقة لإضافة فروع جديدة.")
                ->danger()
                ->send();

            try {
                return redirect()->to(BranchResource::getUrl('index'));
            } catch (\Throwable) {
                return redirect()->back();
            }
        }

        return $next($request);
    }
}
