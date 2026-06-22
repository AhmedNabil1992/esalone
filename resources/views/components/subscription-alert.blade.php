@if(session()->has('subscription_warning'))
    <div class="fi-alert fi-color-warning fi-icon-position-before fi-size-md fi-with-icon fi-alert-contained mb-6">
        <div class="flex items-start gap-3">
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-warning-950">
                    تنبيه: انتهاء الاشتراك قريب
                </h3>
                <p class="text-sm text-warning-800 mt-1">
                    سيتم إنهاء اشتراكك في <strong>{{ session('subscription_warning.days') }}</strong> أيام
                    <br/>
                    تاريخ الانتهاء: <strong>{{ session('subscription_warning.ended_at') }}</strong>
                </p>
            </div>
        </div>
    </div>
@endif

@if(session()->has('subscription_expired'))
    <div class="fi-alert fi-color-danger fi-icon-position-before fi-size-md fi-with-icon fi-alert-contained mb-6">
        <div class="flex items-start gap-3">
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-danger-950">
                    خطر: انتهى الاشتراك
                </h3>
                <p class="text-sm text-danger-800 mt-1">
                    لقد انتهى اشتراكك. يرجى تجديد الاشتراك للمتابعة.
                </p>
            </div>
        </div>
    </div>
@endif
