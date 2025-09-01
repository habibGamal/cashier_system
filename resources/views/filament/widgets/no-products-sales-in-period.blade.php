<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col items-center justify-center py-12 text-center">
            <div class="flex items-center justify-center w-16 h-16 mb-4 bg-gray-100 rounded-full dark:bg-gray-800">
                <x-heroicon-o-chart-bar class="w-8 h-8 text-gray-400" />
            </div>

            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
                لا توجد مبيعات في الفترة المحددة
            </h3>

            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                {{ $description }}
            </p>

            <div class="text-xs text-gray-400 dark:text-gray-500">
                يرجى تجربة فترة زمنية مختلفة أو التأكد من وجود طلبات مكتملة
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
