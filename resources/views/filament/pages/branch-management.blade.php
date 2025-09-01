<x-filament-panels::page>
    <div class="space-y-6">
        @if (app(\App\Services\BranchService::class)->isSlave())
            <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
                <!-- New Products Section -->
                <x-filament::section class="h-full">
                    <x-slot name="heading">
                        <div class="flex items-center gap-3">
                            <x-heroicon-m-plus-circle class="w-5 h-5 text-success-500" />
                            <span>المنتجات الجديدة</span>
                        </div>
                    </x-slot>

                    @if (count($this->newProducts) > 0)
                        <div class="space-y-4">
                            @foreach ($this->newProducts as $category)
                                @if (count($category['products']) > 0)
                                    <x-filament::card class="p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="font-medium text-gray-950 dark:text-white">
                                                {{ $category['name'] }}
                                            </h4>
                                            <x-filament::badge color="primary" size="sm">
                                                {{ count($category['products']) }} منتج
                                            </x-filament::badge>
                                        </div>
                                        <div class="space-y-2">
                                            @foreach (array_slice($category['products'], 0, 3) as $product)
                                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                    <x-heroicon-m-cube class="w-4 h-4 text-gray-400" />
                                                    <span>{{ $product['name'] }}</span>
                                                </div>
                                            @endforeach
                                            @if (count($category['products']) > 3)
                                                <div class="text-sm text-primary-600 dark:text-primary-400 font-medium">
                                                    ... و {{ count($category['products']) - 3 }} منتج آخر
                                                </div>
                                            @endif
                                        </div>
                                    </x-filament::card>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <x-heroicon-o-check-circle class="w-16 h-16 text-success-500 mx-auto mb-4" />
                            <p class="text-gray-500 dark:text-gray-400 text-sm">لا توجد منتجات جديدة متاحة</p>
                        </div>
                    @endif
                </x-filament::section>

                <!-- Changed Prices Section -->
                <x-filament::section class="h-full">
                    <x-slot name="heading">
                        <div class="flex items-center gap-3">
                            <x-heroicon-m-currency-dollar class="w-5 h-5 text-warning-500" />
                            <span>الأسعار المتغيرة</span>
                        </div>
                    </x-slot>

                    @if (count($this->changedPrices) > 0)
                        <div class="space-y-4">
                            @foreach ($this->changedPrices as $category)
                                @if (count($category['products']) > 0)
                                    <x-filament::card class="p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="font-medium text-gray-950 dark:text-white">
                                                {{ $category['name'] }}
                                            </h4>
                                            <x-filament::badge color="warning" size="sm">
                                                {{ count($category['products']) }} منتج
                                            </x-filament::badge>
                                        </div>
                                        <div class="space-y-3">
                                            @foreach (array_slice($category['products'], 0, 3) as $product)
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                        <x-heroicon-m-currency-dollar class="w-4 h-4 text-gray-400" />
                                                        <span>{{ $product['name'] }}</span>
                                                    </div>
                                                    @if (isset($product['price']) || isset($product['cost']))
                                                        <div class="flex items-center gap-2">
                                                            @if (isset($product['price']))
                                                                <x-filament::badge color="success" size="sm">
                                                                    {{ $product['price'] }} EGP
                                                                </x-filament::badge>
                                                            @endif
                                                            @if (isset($product['cost']))
                                                                <x-filament::badge color="info" size="sm">
                                                                    تكلفة: {{ $product['cost'] }}
                                                                </x-filament::badge>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                            @if (count($category['products']) > 3)
                                                <div class="text-sm text-primary-600 dark:text-primary-400 font-medium">
                                                    ... و {{ count($category['products']) - 3 }} منتج آخر
                                                </div>
                                            @endif
                                        </div>
                                    </x-filament::card>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <x-heroicon-o-check-circle class="w-16 h-16 text-success-500 mx-auto mb-4" />
                            <p class="text-gray-500 dark:text-gray-400 text-sm">جميع الأسعار محدثة</p>
                        </div>
                    @endif
                </x-filament::section>

                <!-- Changed Recipes Section -->
                <x-filament::section class="h-full">
                    <x-slot name="heading">
                        <div class="flex items-center gap-3">
                            <x-heroicon-m-squares-2x2 class="w-5 h-5 text-info-500" />
                            <span>الوصفات المتغيرة</span>
                        </div>
                    </x-slot>

                    @if (count($this->changedRecipes) > 0)
                        <div class="space-y-4">
                            @foreach ($this->changedRecipes as $category)
                                @if (count($category['products']) > 0)
                                    <x-filament::card class="p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="font-medium text-gray-950 dark:text-white">
                                                {{ $category['name'] }}
                                            </h4>
                                            <x-filament::badge color="info" size="sm">
                                                {{ count($category['products']) }} منتج
                                            </x-filament::badge>
                                        </div>
                                        <div class="space-y-3">
                                            @foreach (array_slice($category['products'], 0, 3) as $product)
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                        <x-heroicon-m-squares-2x2 class="w-4 h-4 text-gray-400" />
                                                        <span>{{ $product['name'] }}</span>
                                                    </div>
                                                    @if (isset($product['components']))
                                                        <x-filament::badge color="primary" size="sm">
                                                            {{ count($product['components']) }} مكون
                                                        </x-filament::badge>
                                                    @endif
                                                </div>
                                            @endforeach
                                            @if (count($category['products']) > 3)
                                                <div class="text-sm text-primary-600 dark:text-primary-400 font-medium">
                                                    ... و {{ count($category['products']) - 3 }} منتج آخر
                                                </div>
                                            @endif
                                        </div>
                                    </x-filament::card>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <x-heroicon-o-check-circle class="w-16 h-16 text-success-500 mx-auto mb-4" />
                            <p class="text-gray-500 dark:text-gray-400 text-sm">جميع الوصفات محدثة</p>
                        </div>
                    @endif
                </x-filament::section>
            </div>

            <!-- Connection Status -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-3">
                        <x-heroicon-m-signal class="w-5 h-5 text-info-500" />
                        <span>حالة الاتصال بالنقطة الرئيسية</span>
                    </div>
                </x-slot>

                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">حالة الاتصال:</span>
                    @if (app(\App\Services\BranchService::class)->testMasterConnection())
                        <x-filament::badge color="success" icon="heroicon-m-check">
                            متصل
                        </x-filament::badge>
                    @else
                        <x-filament::badge color="danger" icon="heroicon-m-x-mark">
                            غير متصل
                        </x-filament::badge>
                    @endif
                </div>
            </x-filament::section>

            <!-- Instructions -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-3">
                        <x-heroicon-m-information-circle class="w-5 h-5 text-info-500" />
                        <span>تعليمات الاستخدام</span>
                    </div>
                </x-slot>

                <div class="prose dark:prose-invert max-w-none">
                    <ul class="text-sm space-y-2 text-gray-600 dark:text-gray-400">
                        <li class="flex items-start gap-2">
                            <x-heroicon-m-arrow-path class="w-4 h-4 text-primary-500 mt-0.5 shrink-0" />
                            <span>استخدم زر "تحديث البيانات" لجلب أحدث المنتجات والأسعار والوصفات من النقطة الرئيسية</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <x-heroicon-m-plus class="w-4 h-4 text-success-500 mt-0.5 shrink-0" />
                            <span>اختر المنتجات التي تريد استيرادها من قائمة "المنتجات الجديدة"</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <x-heroicon-m-currency-dollar class="w-4 h-4 text-warning-500 mt-0.5 shrink-0" />
                            <span>اختر المنتجات التي تريد تحديث أسعارها من قائمة "الأسعار المتغيرة"</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <x-heroicon-m-squares-2x2 class="w-4 h-4 text-info-500 mt-0.5 shrink-0" />
                            <span>اختر المنتجات التي تريد تحديث وصفاتها من قائمة "الوصفات المتغيرة"</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <x-heroicon-m-signal class="w-4 h-4 text-info-500 mt-0.5 shrink-0" />
                            <span>تأكد من أن الاتصال بالنقطة الرئيسية يعمل بشكل صحيح</span>
                        </li>
                    </ul>
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                <div class="text-center py-16">
                    <x-heroicon-o-exclamation-triangle class="w-20 h-20 text-warning-500 mx-auto mb-6" />
                    <div class="space-y-4">
                        <h3 class="text-xl font-semibold text-gray-950 dark:text-white">
                            هذه الصفحة متاحة فقط للفروع
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 max-w-md mx-auto">
                            يجب تعيين نوع النقطة الحالية كـ "فرع" في الإعدادات لاستخدام هذه الميزة
                        </p>
                        <div class="pt-4">
                            <x-filament::button
                                tag="a"
                                href="{{ route('filament.admin.pages.settings') }}"
                                icon="heroicon-m-cog-6-tooth"
                                color="primary"
                            >
                                الذهاب إلى الإعدادات
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
