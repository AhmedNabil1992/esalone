<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\OpenShiftAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuickOpenShiftController extends Controller
{
    public function __invoke(Request $request, OpenShiftAction $openShift): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'integer', 'min:1'],
            'redirect' => ['nullable', 'string', 'max:2048'],
        ]);
        $openShift->execute($request->user(), $data);

        $redirect = $data['redirect'] ?? null;
        if (is_string($redirect) && str_starts_with($redirect, '/')
            && ! str_starts_with($redirect, '//') && ! str_contains($redirect, '\\')
            && ! preg_match('/[\x00-\x1F\x7F]/', $redirect)) {
            return redirect()->to($redirect);
        }

        return redirect()->route('filament.admin.resources.tenant.shifts.shifts.index');
    }
}
