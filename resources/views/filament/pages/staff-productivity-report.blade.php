<x-filament-panels::page>
    @php($rows = $this->getStaffProductivityRows())

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
            تُحتسب النتائج بحسب تاريخ الموعد، والإيرادات من الفواتير المُرحّلة المرتبطة بمواعيد مكتملة. الإشغال يقارن مدة المواعيد المجدولة والمؤكدة وقيد التنفيذ والمكتملة بساعات الدوام الأسبوعي المسجل، ولا يشمل الطلبات الجديدة أو المواعيد الملغاة أو حالات عدم الحضور. نطاق الفرع يتبع الفرع النشط وصلاحيات المستخدم.
        </p>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] divide-y divide-gray-200 text-start dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">
                            <th scope="col" class="px-4 py-3">الموظف</th>
                            <th scope="col" class="px-4 py-3">المواعيد</th>
                            <th scope="col" class="px-4 py-3">مكتمل / محصّل</th>
                            <th scope="col" class="px-4 py-3">إيراد الفواتير المُرحّلة</th>
                            <th scope="col" class="px-4 py-3">العمولة المسجلة</th>
                            <th scope="col" class="px-4 py-3">الساعات المشغولة</th>
                            <th scope="col" class="px-4 py-3">الإشغال</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($rows as $row)
                            <tr class="text-sm text-gray-700 dark:text-gray-200">
                                <th scope="row" class="px-4 py-4 text-start font-medium text-gray-950 dark:text-white">
                                    {{ $row['name'] }}
                                    @if($row['job_title'])
                                        <span class="mt-1 block text-xs font-normal text-gray-500 dark:text-gray-400">{{ $row['job_title'] }}</span>
                                    @endif
                                </th>
                                <td class="px-4 py-4">{{ number_format($row['appointments']) }}</td>
                                <td class="px-4 py-4">{{ number_format($row['completed']) }} / {{ number_format($row['collected_appointments']) }}</td>
                                <td class="px-4 py-4">{{ number_format($row['collected_revenue'], 2) }} ج.م</td>
                                <td class="px-4 py-4">{{ number_format($row['commission'], 2) }} ج.م</td>
                                <td class="px-4 py-4">{{ number_format($row['occupied_minutes'] / 60, 1) }} ساعة</td>
                                <td class="px-4 py-4">
                                    @if($row['occupancy_rate'] === null)
                                        <span class="text-gray-500 dark:text-gray-400">غير محدد — لا يوجد دوام مسجل</span>
                                    @else
                                        <span class="font-medium">{{ number_format($row['occupancy_rate'], 1) }}٪</span>
                                        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                                            من {{ number_format(($row['available_minutes'] ?? 0) / 60, 1) }} ساعة متاحة
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                    لا يوجد موظفون أو نشاط خلال الفترة المحددة.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
