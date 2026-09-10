<?php

declare(strict_types=1);

namespace App\Support\Tenant;

use LogicException;

class PublicInvoiceToken
{
    public function generate(int|string $invoiceId): string
    {
        if (! tenancy()->initialized || ! tenant()?->getTenantKey()) {
            throw new LogicException('Public invoice links require a tenant context.');
        }

        $key = (string) config('app.key');
        if ($key === '') {
            throw new LogicException('An application key is required.');
        }

        $payload = json_encode(['public-invoice-v2', (string) tenant()->getTenantKey(), (string) $invoiceId], JSON_THROW_ON_ERROR);

        return hash_hmac('sha256', $payload, $key);
    }

    public function isValid(int|string $invoiceId, string $token): bool
    {
        return tenancy()->initialized && hash_equals($this->generate($invoiceId), $token);
    }
}
