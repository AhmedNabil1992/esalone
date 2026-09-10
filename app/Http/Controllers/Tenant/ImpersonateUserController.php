<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Stancl\Tenancy\Features\UserImpersonation;

class ImpersonateUserController extends Controller
{
    public function __invoke(string $token): RedirectResponse
    {
        $tenantTimezone = date_default_timezone_get();

        try {
            date_default_timezone_set((string) config('app.timezone', 'UTC'));

            return UserImpersonation::makeResponse($token);
        } finally {
            date_default_timezone_set($tenantTimezone);
        }
    }
}
