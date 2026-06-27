<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\OrderSessionDetail;
use App\Models\Tenant\SystemConfig;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PrintOrderPrepareController extends Controller
{
    public function print(Request $request): View
    {
        if (! $this->isPrintActive()) {
            throw new NotFoundHttpException;
        }

        $idsString = $request->query('ids', '');
        $ids = array_filter(explode(',', $idsString), 'is_numeric');

        if (empty($ids)) {
            throw new NotFoundHttpException('No orders to print.');
        }

        $orders = OrderSessionDetail::query()
            ->whereIn('id', $ids)
            ->with(['catalogItem.inventoryGroup', 'device', 'playSession'])
            ->get();

        if ($orders->isEmpty()) {
            throw new NotFoundHttpException('No orders found.');
        }

        $deviceName = $orders->first()->device?->name ?? '—';
        $orderTime = $orders->first()->created_at ? $orders->first()->created_at->format('h:i:s a') : now()->format('h:i:s a');

        return view('tenant.prints.order-prepare', [
            'orders' => $orders,
            'deviceName' => $deviceName,
            'orderTime' => $orderTime,
        ]);
    }

    private function isPrintActive(): bool
    {
        return in_array((string) SystemConfig::get('print_active', '0'), ['1', 'true', 'on', 'yes'], true);
    }
}
