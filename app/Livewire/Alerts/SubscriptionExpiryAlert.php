<?php

namespace App\Livewire\Alerts;

use Livewire\Component;

class SubscriptionExpiryAlert extends Component
{
    public function render()
    {
        return view('livewire.alerts.subscription-expiry-alert', [
            'daysRemaining' => session('subscription_warning.days'),
            'endedAt' => session('subscription_warning.ended_at'),
            'isExpired' => session()->has('subscription_expired'),
        ]);
    }
}
