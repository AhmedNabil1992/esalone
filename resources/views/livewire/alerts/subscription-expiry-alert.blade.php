<div>
    {{-- DEBUG: عرض القيم --}}
    {{-- daysRemaining: {{ $daysRemaining }}, isExpired: {{ $isExpired ? 'true' : 'false' }} --}}

    @if($isExpired)
        <div class="mx-auto max-w-7xl px-6 py-12 sm:px-4 lg:px-8">
            <div class="rounded-lg border border-red-200 bg-red-50 p-6">
                <div class="flex items-center gap-3">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="font-semibold text-red-800">انتهى الاشتراك</h3>
                        <p class="text-sm text-red-700 mt-1">
                            لقد انتهى اشتراكك. يرجى تجديد الاشتراك للمتابعة في استخدام النظام.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @elseif($daysRemaining && (int)$daysRemaining <= 3)
        <div class="mx-auto max-w-7xl px-6 py-12 sm:px-4 lg:px-8">
            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-6">
                <div class="flex items-center gap-3">
                    <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v2m0 0h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="font-semibold text-yellow-800">تنبيه: انتهاء الاشتراك قريب</h3>
                        <p class="text-sm text-yellow-700 mt-1">
                            سيتم إنهاء اشتراكك خلال <strong>{{ (int)$daysRemaining }}</strong> أيام
                            @if($endedAt)
                                <br/>تاريخ الانتهاء: <strong>{{ $endedAt }}</strong>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
