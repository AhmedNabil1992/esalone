<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Filament\Resources\Tenant\Shifts\Shifts\ShiftResource;
use App\Http\Controllers\Controller;
use App\Models\Tenant\CashBox;
use App\Models\Tenant\Shift;
use App\Models\Tenant\SystemConfig;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PrintShiftCloseController extends Controller
{
    public function __invoke(Shift $shift): View
    {
        abort_unless(
            Auth::user()?->can('View:Shift') === true
                || Auth::user()?->can('Create:Shift') === true
                || Auth::user()?->can('Update:Shift') === true,
            403,
        );

        if ($shift->status !== 'close') {
            throw new NotFoundHttpException;
        }

        $shift->load(['branch', 'opener', 'closer']);
        $cashBoxActualAmounts = collect($shift->cashbox_actual_amounts ?? [])
            ->mapWithKeys(fn (mixed $amount, int|string $cashBoxId): array => [(int) $cashBoxId => (float) $amount]);
        $cashBoxes = CashBox::query()
            ->where('branch_id', $shift->branch_id)
            ->whereIn('id', $cashBoxActualAmounts->keys())
            ->get()
            ->map(fn (CashBox $cashBox): array => [
                'name' => $cashBox->name,
                'actual_amount' => $cashBoxActualAmounts->get($cashBox->id, 0.0),
            ]);

        return view('tenant.prints.shift-close', [
            'shift' => $shift,
            'cashBoxes' => $cashBoxes,
            'logoPath' => (string) SystemConfig::get('print_logo_path', ''),
            'shopName' => (string) SystemConfig::get('sys_shop_name', 'PS Web'),
            'footerMessage' => (string) SystemConfig::get('print_footer_message', ''),
            'returnUrl' => ShiftResource::getUrl('index'),
            'autoPrint' => request()->boolean('auto'),
            'bridgeUrl' => (string) (config('services.bridge.url') ?: 'ws://127.0.0.1:9000'),
            'printerName' => (string) SystemConfig::get('sys_invoice_printer', ''),
        ]);
    }
}
