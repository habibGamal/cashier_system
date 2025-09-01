<x-filament-panels::page>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            .print-break {
                page-break-before: always;
            }

            body {
                font-size: 12px;
            }

            .bg-linear-to-r {
                background: #f8f9fa !important;
            }

            .shadow {
                box-shadow: none !important;
            }
        }
    </style>

    <div class="space-y-6">
        <!-- Filter Form -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 no-print border border-gray-200 dark:border-gray-700">
            {{ $this->form }}
        </div>

        <!-- Shift Info -->
        @if ($selectedShiftInfo)
            <div class="bg-blue-50 dark:bg-blue-900 border-l-4 border-blue-400 dark:border-blue-500 p-4 rounded-sm">
                <div class="flex">
                    <div class="shrink-0">
                        <x-heroicon-s-information-circle class="h-5 w-5 text-blue-400 dark:text-blue-300" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            معلومات الوردية المختارة
                        </h3>
                        <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                            {{ $selectedShiftInfo }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Log Entries -->
        @if (count($logEntries) > 0)
            <!-- Summary Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                @php
                    $totalEntries = count($logEntries);
                    $orderActions = collect($logEntries)->where('context.order_id', '!=', null)->count();
                    $errorCount = collect($logEntries)->where('level', 'error')->count();
                    $uniqueOrders = collect($logEntries)->pluck('context.order_id')->filter()->unique()->count();
                @endphp

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
                    <div class="flex items-center gap-2">
                        <x-heroicon-s-document-text class="w-8 h-8 text-blue-500" />
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">إجمالي الأنشطة</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalEntries }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-green-500">
                    <div class="flex items-center gap-2">
                        <x-heroicon-s-shopping-cart class="w-8 h-8 text-green-500" />
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">أنشطة الطلبات</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $orderActions }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-purple-500">
                    <div class="flex items-center gap-2">
                        <x-heroicon-s-hashtag class="w-8 h-8 text-purple-500" />
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">طلبات منفردة</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $uniqueOrders }}</p>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-{{ $errorCount > 0 ? 'red' : 'gray' }}-500">
                    <div class="flex items-center gap-2">
                        <x-heroicon-s-exclamation-triangle
                            class="w-8 h-8 text-{{ $errorCount > 0 ? 'red' : 'gray' }}-500" />
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">الأخطاء</p>
                            <p class="text-2xl font-bold text-{{ $errorCount > 0 ? 'red' : 'gray' }}-900 dark:text-{{ $errorCount > 0 ? 'red' : 'gray' }}-100">
                                {{ $errorCount }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        سجل الأنشطة التفصيلي
                    </h3>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($logEntries as $entry)
                        <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <div class="flex items-start space-x-4 space-x-reverse">
                                <!-- Icon with enhanced styling -->
                                <div class="shrink-0">
                                    <div
                                        class="w-10 h-10 rounded-full flex items-center justify-center {{ $this->getLogLevelClass($entry['level']) }} shadow-xs">
                                        @php
                                            $iconClass = $this->getLogLevelIcon($entry['level']);
                                        @endphp
                                        <x-dynamic-component :component="$iconClass" class="w-5 h-5" />
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <!-- Header with action and timestamp -->
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex-1">
                                            <!-- Main Action Message -->
                                            <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1">
                                                {{ $entry['message'] }}
                                            </h4>

                                            <!-- Quick summary from context -->
                                            @if (isset($entry['context']['order_id']))
                                                <div
                                                    class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600 dark:text-gray-400">
                                                    <x-heroicon-s-hashtag class="w-4 h-4" />
                                                    <span>الطلب رقم: <span
                                                            class="font-bold text-gray-800 dark:text-gray-200">#{{ $entry['context']['order_id'] }}</span></span>
                                                    @if (isset($entry['context']['customer_name']))
                                                        <span class="text-gray-400 dark:text-gray-500">|</span>
                                                        <span>العميل: <span
                                                                class="font-medium text-gray-700 dark:text-gray-300">{{ $entry['context']['customer_name'] }}</span></span>
                                                    @endif
                                                    @if (isset($entry['context']['total_amount']))
                                                        <span class="text-gray-400 dark:text-gray-500">|</span>
                                                        <span>المبلغ: <span
                                                                class="font-bold text-green-600 dark:text-green-400">{{ $this->formatMoney($entry['context']['total_amount']) }}</span></span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Timestamp and Level Badge -->
                                        <div class="flex flex-col items-end space-y-1">
                                            <div class="text-right">
                                                <div class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                                    {{ $entry['formatted_time'] }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $entry['formatted_date'] }}</div>
                                            </div>
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $this->getLogLevelClass($entry['level']) }} shadow-xs">
                                                @switch($entry['level'])
                                                    @case('error')
                                                        خطأ
                                                    @break

                                                    @case('warning')
                                                        تحذير
                                                    @break

                                                    @case('info')
                                                        معلومات
                                                    @break

                                                    @default
                                                        {{ $entry['level'] }}
                                                @endswitch
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Context Details -->
                                    @if (!empty($entry['context']))
                                        <div class="mt-4 space-y-3">
                                            @foreach ($entry['context'] as $key => $value)
                                                @if ($key === 'differences' && is_array($value) && !empty($value))
                                                    <!-- Order Changes Section -->
                                                    <div
                                                        class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                                                        <div class="flex items-center mb-3">
                                                            <x-heroicon-s-list-bullet
                                                                class="w-5 h-5 text-blue-600 dark:text-blue-400 ml-2" />
                                                            <h5 class="text-sm font-bold text-blue-800 dark:text-blue-200">
                                                                تفاصيل التغييرات ({{ count($value) }} تغيير)
                                                            </h5>
                                                        </div>
                                                        <div class="space-y-3">
                                                            @foreach ($value as $difference)
                                                                <div
                                                                    class="bg-white dark:bg-gray-800 rounded-md p-3 border border-blue-100 dark:border-blue-800">
                                                                    <div
                                                                        class="flex items-start space-x-3 space-x-reverse">
                                                                        @php
                                                                            $typeIcon = $this->getActionTypeIcon(
                                                                                $difference['type'] ?? '',
                                                                            );
                                                                            $typeColor = $this->getActionTypeColor(
                                                                                $difference['type'] ?? '',
                                                                            );
                                                                        @endphp
                                                                        <x-dynamic-component :component="$typeIcon"
                                                                            class="w-5 h-5 mt-0.5 {{ $typeColor }} shrink-0" />
                                                                        <div class="flex-1">
                                                                            <p
                                                                                class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                                                                {{ $difference['description'] ?? '' }}
                                                                            </p>
                                                                            @if (isset($difference['product_name']))
                                                                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                                                    <span
                                                                                        class="font-medium">المنتج:</span>
                                                                                    {{ $difference['product_name'] }}
                                                                                </p>
                                                                            @endif
                                                                            @if (isset($difference['old_quantity']) && isset($difference['new_quantity']))
                                                                                <div
                                                                                    class="text-xs text-gray-600 mt-1 flex space-x-4 space-x-reverse">
                                                                                    <span><span
                                                                                            class="font-medium">الكمية
                                                                                            السابقة:</span>
                                                                                        {{ $difference['old_quantity'] }}</span>
                                                                                    <span><span
                                                                                            class="font-medium">الكمية
                                                                                            الجديدة:</span>
                                                                                        {{ $difference['new_quantity'] }}</span>
                                                                                    @if (isset($difference['difference']))
                                                                                        <span
                                                                                            class="font-medium {{ $difference['difference'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                                                            ({{ $difference['difference'] > 0 ? '+' : '' }}{{ $difference['difference'] }})
                                                                                        </span>
                                                                                    @endif
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @elseif($key === 'payment_methods' && is_array($value) && !empty($value))
                                                    <!-- Payment Methods Section -->
                                                    <div
                                                        class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-4">
                                                        <div class="flex items-center mb-3">
                                                            <x-heroicon-s-credit-card
                                                                class="w-5 h-5 text-green-600 dark:text-green-400 ml-2" />
                                                            <h5 class="text-sm font-bold text-green-800 dark:text-green-200">
                                                                طرق الدفع المستخدمة
                                                            </h5>
                                                        </div>
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                            @foreach ($value as $payment)
                                                                <div
                                                                    class="bg-white dark:bg-gray-800 rounded-md p-2 border border-green-100 dark:border-green-800">
                                                                    <span
                                                                        class="text-sm font-medium text-green-800 dark:text-green-200">{{ $payment }}</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @elseif($key === 'updated_items' && is_array($value) && !empty($value))
                                                    <!-- Updated Items Section -->
                                                    <div
                                                        class="bg-orange-50 dark:bg-orange-900 border border-orange-200 dark:border-orange-700 rounded-lg p-4">
                                                        <div class="flex items-center mb-3">
                                                            <x-heroicon-s-pencil-square
                                                                class="w-5 h-5 text-orange-600 dark:text-orange-400 ml-2" />
                                                            <h5 class="text-sm font-bold text-orange-800 dark:text-orange-200">
                                                                العناصر المحدثة ({{ count($value) }} عنصر)
                                                            </h5>
                                                        </div>
                                                        <div class="space-y-3">
                                                            @foreach ($value as $item)
                                                                <div
                                                                    class="bg-white dark:bg-gray-800 rounded-md p-3 border border-orange-100 dark:border-orange-800">
                                                                    <h6 class="text-sm font-bold text-orange-800 dark:text-orange-200 mb-2">
                                                                        {{ $item['product_name'] ?? 'غير محدد' }}
                                                                    </h6>
                                                                    @if (isset($item['old_notes']) || isset($item['new_notes']))
                                                                        <div class="space-y-1 text-xs">
                                                                            <div class="flex space-x-2 space-x-reverse">
                                                                                <span
                                                                                    class="font-medium text-gray-600 dark:text-gray-400 min-w-fit">الملاحظات
                                                                                    السابقة:</span>
                                                                                <span
                                                                                    class="text-gray-800 dark:text-gray-200 bg-red-50 dark:bg-red-900 px-2 py-1 rounded-sm">
                                                                                    "{{ $item['old_notes'] ?? 'بدون ملاحظات' }}"
                                                                                </span>
                                                                            </div>
                                                                            <div class="flex space-x-2 space-x-reverse">
                                                                                <span
                                                                                    class="font-medium text-gray-600 dark:text-gray-400 min-w-fit">الملاحظات
                                                                                    الجديدة:</span>
                                                                                <span
                                                                                    class="text-gray-800 dark:text-gray-200 bg-green-50 dark:bg-green-900 px-2 py-1 rounded-sm">
                                                                                    "{{ $item['new_notes'] ?? 'بدون ملاحظات' }}"
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @elseif(
                                                    !in_array($key, ['timestamp', 'shift_id', 'user_id', 'user_name', 'action', 'details']) &&
                                                        $value !== null &&
                                                        $value !== '')
                                                    <!-- Regular Context Fields -->
                                                    @if (!is_array($value))
                                                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                                                            <div class="flex items-center justify-between">
                                                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                                    {{ $this->getContextKeyLabel($key) }}
                                                                </span>
                                                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                                    @if ($this->isMonetaryKey($key))
                                                                        {{ $this->formatMoney($value) }}
                                                                    @elseif($key === 'discount_display')
                                                                        <span
                                                                            class="bg-amber-100 dark:bg-amber-800 text-amber-800 dark:text-amber-200 px-2 py-1 rounded-full text-xs font-bold">
                                                                            {{ $value }}
                                                                        </span>
                                                                    @elseif($key === 'payment_summary')
                                                                        <span
                                                                            class="bg-emerald-100 dark:bg-emerald-800 text-emerald-800 dark:text-emerald-200 px-2 py-1 rounded-full text-xs font-bold">
                                                                            {{ $value }}
                                                                        </span>
                                                                    @elseif($key === 'total_changes' || $key === 'changes_count')
                                                                        <span
                                                                            class="bg-sky-100 dark:bg-sky-800 text-sky-800 dark:text-sky-200 px-2 py-1 rounded-full text-xs font-bold">
                                                                            {{ $value }}
                                                                            {{ $value == 1 ? 'تغيير' : 'تغييرات' }}
                                                                        </span>
                                                                    @elseif(str_contains($key, 'phone'))
                                                                        <a href="tel:{{ $value }}"
                                                                            class="text-blue-600 hover:text-blue-800 font-mono">
                                                                            {{ $value }}
                                                                        </a>
                                                                    @else
                                                                        {{ $this->formatContextValue($value) }}
                                                                    @endif
                                                                </span>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endif
                                            @endforeach

                                            <!-- User and Timing Info -->
                                            @if (isset($entry['context']['user_name']) || isset($entry['context']['timestamp']))
                                                <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-3 border border-slate-200 dark:border-slate-700 mt-4">
                                                    <div
                                                        class="flex items-center space-x-4 space-x-reverse text-xs text-slate-700 dark:text-slate-300">
                                                        @if (isset($entry['context']['user_name']))
                                                            <div class="flex items-center space-x-1 space-x-reverse">
                                                                <x-heroicon-s-user class="w-4 h-4" />
                                                                <span class="font-medium">المستخدم:</span>
                                                                <span
                                                                    class="font-bold">{{ $entry['context']['user_name'] }}</span>
                                                            </div>
                                                        @endif
                                                        @if (isset($entry['context']['shift_id']))
                                                            <div class="flex items-center space-x-1 space-x-reverse">
                                                                <x-heroicon-s-clock class="w-4 h-4" />
                                                                <span class="font-medium">رقم الوردية:</span>
                                                                <span
                                                                    class="font-bold">#{{ $entry['context']['shift_id'] }}</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <!-- No context data available -->
                                        <div class="mt-3 text-sm text-gray-500 dark:text-gray-400 italic">
                                            لا توجد تفاصيل إضافية متاحة لهذا النشاط
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif($data['shift_id'] ?? null)
            <!-- No logs found for selected shift -->
            <div class="bg-yellow-50 dark:bg-yellow-900 border-l-4 border-yellow-400 dark:border-yellow-500 p-4 rounded-sm">
                <div class="flex">
                    <div class="shrink-0">
                        <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-yellow-400 dark:text-yellow-300" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            لا توجد أنشطة مسجلة
                        </h3>
                        <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                            لم يتم العثور على أي أنشطة مسجلة للوردية المختارة.
                        </p>
                    </div>
                </div>
            </div>
        @else
            <!-- No shift selected -->
            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-8 text-center">
                <x-heroicon-o-document-text class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">اختر وردية لعرض السجل</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    اختر وردية من القائمة أعلاه لعرض سجل الأنشطة الخاص بها.
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
