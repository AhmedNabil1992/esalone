<x-filament-panels::page>
    @php($report = $this->getReportData())

    <div class="space-y-6">
        <form wire:submit="applyFilters" class="flex flex-wrap items-end gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                <span>من تاريخ</span>
                <input type="date" wire:model="from" class="rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                @error('from') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </label>
            <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                <span>إلى تاريخ</span>
                <input type="date" wire:model="until" class="rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                @error('until') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </label>
            <x-filament::button type="submit" icon="heroicon-o-funnel">
                تطبيق الفترة
            </x-filament::button>
        </form>

        <p class="text-sm text-gray-600 dark:text-gray-300">
            يعرض التقرير المواعيد بحسب تاريخ بدايتها. تُعدّ الفاتورة محصّلة عند ترحيلها، ويُحسب معدل التحصيل من المواعيد المكتملة بصورة مستقلة عن معدل التحويل من جميع الحجوزات.
        </p>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">إجمالي الحجوزات</p>
                <p class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ number_format($report['total']) }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">المواعيد المكتملة</p>
                <p class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ number_format($report['completed']) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">معدل الإكمال: {{ number_format($report['completion_rate'], 1) }}٪</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">مواعيد بفواتير مُرحّلة</p>
                <p class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ number_format($report['collected']) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">من جميع الحجوزات: {{ number_format($report['booking_collection_rate'], 1) }}٪</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">مكتملة دون فاتورة مُرحّلة</p>
                <p class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ number_format($report['completed_without_invoice']) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">معدل التحصيل للمكتمل: {{ number_format($report['completion_collection_rate'], 1) }}٪</p>
            </article>
        </div>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">توزيع حالات المواعيد</h2>
            @if($report['statuses']->isEmpty())
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">لا توجد مواعيد خلال الفترة المحددة.</p>
            @else
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                    @foreach($report['statuses'] as $status)
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $status['label'] }}</p>
                            <p class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">{{ number_format($status['count']) }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
