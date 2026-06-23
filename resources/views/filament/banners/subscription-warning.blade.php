@php
    // Only show on dashboard page
    $isDashboard = str_contains(request()->route()?->getName() ?? '', 'dashboard');
@endphp

@if ($isDashboard)
    @php
        $tenant = tenant();
        $show = false;
        $isExpired = false;
        $daysRemaining = 0;
        $endedAt = null;

        if ($tenant && $tenant->ended_at) {
            $daysRemaining = (int) $tenant->remainingDays();
            $isExpired = $tenant->isSubscriptionExpired();
            $endedAt = $tenant->ended_at->format('d/m/Y');

            if ($isExpired || $daysRemaining < 7) {
                $show = true;
            }
        }
    @endphp

    @if ($show)
        @if ($isExpired)
            <div class="px-4 py-2 text-sm text-red-700 bg-red-100 border-b border-red-300" dir="rtl">
                <strong>⚠️ انتبه:</strong> انتهى اشتراكك بتاريخ {{ $endedAt }}
            </div>
        @elseif ($daysRemaining === 0)
            <div class="px-4 py-2 text-sm text-yellow-700 bg-yellow-100 border-b border-yellow-300" dir="rtl">
                <strong>⏰ تنبيه:</strong> اشتراكك ينتهي اليوم!
            </div>
        @else
            <div class="px-4 py-2 text-sm text-yellow-700 bg-yellow-100 border-b border-yellow-300" dir="rtl">
                <strong>⏰ تنبيه:</strong> اشتراكك سينتهي خلال <strong>{{ $daysRemaining }}</strong> يوم بتاريخ {{ $endedAt }}
            </div>
        @endif
    @endif
@endif