<div class="filament-table-footer bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Paid -->
        <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-lg border border-green-200 dark:border-green-800">
            <div class="text-green-800 dark:text-green-200 text-sm font-medium">إجمالي المدفوع</div>
            <div class="text-green-900 dark:text-green-100 text-lg font-bold">{{ number_format($totalPaid, 2) }} ج.م</div>
            <div class="text-green-600 dark:text-green-400 text-xs">{{ $paymentCount }} دفعة</div>
        </div>

        <!-- Cash Payments -->
        @if($cashPayments > 0)
        <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-200 dark:border-blue-800">
            <div class="text-blue-800 dark:text-blue-200 text-sm font-medium">نقدي</div>
            <div class="text-blue-900 dark:text-blue-100 text-lg font-bold">{{ number_format($cashPayments, 2) }} ج.م</div>
        </div>
        @endif

        <!-- Card Payments -->
        @if($cardPayments > 0)
        <div class="bg-purple-50 dark:bg-purple-900/20 p-3 rounded-lg border border-purple-200 dark:border-purple-800">
            <div class="text-purple-800 dark:text-purple-200 text-sm font-medium">بطاقة</div>
            <div class="text-purple-900 dark:text-purple-100 text-lg font-bold">{{ number_format($cardPayments, 2) }} ج.م</div>
        </div>
        @endif

        <!-- Talabat Payments -->
        @if($talabatPayments > 0)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded-lg border border-yellow-200 dark:border-yellow-800">
            <div class="text-yellow-800 dark:text-yellow-200 text-sm font-medium">بطاقة طلبات</div>
            <div class="text-yellow-900 dark:text-yellow-100 text-lg font-bold">{{ number_format($talabatPayments, 2) }} ج.م</div>
        </div>
        @endif

        <!-- Remaining Amount -->
        @if($remainingAmount > 0)
        <div class="bg-red-50 dark:bg-red-900/20 p-3 rounded-lg border border-red-200 dark:border-red-800">
            <div class="text-red-800 dark:text-red-200 text-sm font-medium">المبلغ المتبقي</div>
            <div class="text-red-900 dark:text-red-100 text-lg font-bold">{{ number_format($remainingAmount, 2) }} ج.م</div>
        </div>
        @endif
    </div>

    <!-- Order Total Summary -->
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-center">
            <span class="text-gray-600 dark:text-gray-400 font-medium">إجمالي الطلب:</span>
            <span class="text-gray-900 dark:text-gray-100 font-bold text-xl">{{ number_format($orderTotal, 2) }} ج.م</span>
        </div>
        @if($remainingAmount <= 0)
        <div class="flex justify-between items-center mt-2">
            <span class="text-green-600 dark:text-green-400 font-medium">الحالة:</span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200">
                مدفوع بالكامل
            </span>
        </div>
        @else
        <div class="flex justify-between items-center mt-2">
            <span class="text-red-600 dark:text-red-400 font-medium">الحالة:</span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200">
                دفع جزئي
            </span>
        </div>
        @endif
    </div>
</div>
