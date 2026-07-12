<?php

namespace App\Jobs\Tenant;

use App\Actions\Tenant\SendInvoiceWhatsAppAction;
use App\Models\Tenant;
use App\Models\Tenant\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Stancl\Tenancy\Facades\Tenancy;

class SendInvoiceWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public string $tenantId,
        public int $invoiceId,
        public ?string $manualPhone = null
    ) {
        $this->onConnection('database');
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $tenant = Tenant::query()->find($this->tenantId);
        if (! $tenant) {
            return;
        }

        try {
            Tenancy::initialize($tenant);

            $invoice = Invoice::query()->find($this->invoiceId);
            if (! $invoice) {
                return;
            }

            app(SendInvoiceWhatsAppAction::class)->execute($invoice, $this->manualPhone);
        } finally {
            Tenancy::end();
        }
    }
}
