<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\SystemConfig;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PrintPlayInvoiceController extends Controller
{
    public function __invoke(Invoice $invoice): View
    {
        if (! $this->isPrintActive()) {
            throw new NotFoundHttpException;
        }

        $invoice->load([
            'device.type',
            'customer',
            'cashBox',
            'shift',
            'createdBy',
            'playSessionDetails.device',
            'orderSessionDetails.catalogItem',
            'payments.cashBox',
            'payments.createdBy',
        ]);

        return view('tenant.prints.play-invoice', [
            'invoice' => $invoice,
            'logoPath' => (string) SystemConfig::get('print_logo_path', ''),
            'footerMessage' => (string) SystemConfig::get('print_footer_message', ''),
            'shopName' => (string) SystemConfig::get('sys_shop_name', 'PS Web'),
        ]);
    }

    private function isPrintActive(): bool
    {
        return in_array((string) SystemConfig::get('print_active', '0'), ['1', 'true', 'on', 'yes'], true);
    }
}
