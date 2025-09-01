<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-between items-center pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="flex gap-3">
                    @foreach ($this->getFormActions() as $action)
                        {{ $action }}
                    @endforeach
                </div>

                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-2">
                        <x-heroicon-m-information-circle class="w-4 h-4" />
                        سيتم حفظ الإعدادات تلقائياً في قاعدة البيانات
                    </span>
                </div>
            </div>
        </form>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
