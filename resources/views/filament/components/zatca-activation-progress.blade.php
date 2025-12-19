<div class="prose dark:prose-invert max-w-none">
    <div class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-16 w-16 border-b-2 border-primary-600 mb-4"></div>
        <h3 class="text-lg font-bold mb-2">جاري التفعيل...</h3>
        <p class="text-gray-600 dark:text-gray-400">يرجى الانتظار، هذا قد يستغرق بضع دقائق</p>

        <div class="mt-6 space-y-2 text-sm text-right">
            <div class="flex items-center gap-2">
                <x-filament::loading-indicator class="w-4 h-4" />
                <span>طلب شهادة الامتثال (CSID)...</span>
            </div>
            <div class="flex items-center gap-2">
                <x-filament::loading-indicator class="w-4 h-4" />
                <span>التحقق من الفواتير التجريبية...</span>
            </div>
            <div class="flex items-center gap-2">
                <x-filament::loading-indicator class="w-4 h-4" />
                <span>طلب شهادة الإنتاج (PCSID)...</span>
            </div>
        </div>
    </div>
</div>
