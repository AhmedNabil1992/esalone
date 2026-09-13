<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return ! tenancy()->initialized && $user instanceof User && (string) $user->id === (string) $id;
});

Broadcast::channel('App.Models.Tenant.User.{id}', function ($user, $id) {
    return ! tenancy()->initialized && $user instanceof App\Models\Tenant\User && (string) $user->id === (string) $id;
});

Broadcast::channel('tenant.{tenantId}.users.{id}', function ($user, string $tenantId, string $id): bool {
    return tenancy()->initialized
        && $user instanceof App\Models\Tenant\User
        && (string) tenant()->getTenantKey() === $tenantId
        && (string) $user->getAuthIdentifier() === $id;
});
