<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\SystemConfig;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PublicInvoiceController extends Controller
{
    public function __invoke(Invoice $invoice, string $token): View
    {
        $expectedToken = substr(hash_hmac('sha256', (string) $invoice->id, config('app.key')), 0, 16);

        if (! hash_equals($expectedToken, $token)) {
            throw new AccessDeniedHttpException('رابط غير صالح أو منتهي الصلاحية.');
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
            'noPrint' => true,
        ]);
    }
}
