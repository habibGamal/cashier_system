<x-filament-panels::page>
    <form wire:submit="saveSettings" class="space-y-6">
        {{ $this->form }}

        <x-filament::actions :actions="$this->getFormActions()" />
    </form>

    @if($certificateStatus['csr_exists'] || $certificateStatus['compliance_certificate_exists'] || $certificateStatus['production_certificate_exists'])
        <x-filament::section>
            <x-slot name="heading">
                حالة الشهادات
            </x-slot>

            <x-slot name="description">
                حالة الشهادات والملفات المطلوبة
            </x-slot>

            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    @if($certificateStatus['csr_exists'])
                        <x-filament::icon icon="heroicon-o-check-circle" class="w-5 h-5 text-success-500" />
                    @else
                        <x-filament::icon icon="heroicon-o-x-circle" class="w-5 h-5 text-gray-400" />
                    @endif
                    <span>شهادة الطلب (CSR)</span>
                </div>

                <div class="flex items-center gap-2">
                    @if($certificateStatus['private_key_exists'])
                        <x-filament::icon icon="heroicon-o-check-circle" class="w-5 h-5 text-success-500" />
                    @else
                        <x-filament::icon icon="heroicon-o-x-circle" class="w-5 h-5 text-gray-400" />
                    @endif
                    <span>المفتاح الخاص</span>
                </div>

                <div class="flex items-center gap-2">
                    @if($certificateStatus['compliance_certificate_exists'])
                        <x-filament::icon icon="heroicon-o-check-circle" class="w-5 h-5 text-success-500" />
                    @else
                        <x-filament::icon icon="heroicon-o-x-circle" class="w-5 h-5 text-gray-400" />
                    @endif
                    <span>شهادة الامتثال (CSID)</span>
                </div>

                <div class="flex items-center gap-2">
                    @if($certificateStatus['production_certificate_exists'])
                        <x-filament::icon icon="heroicon-o-check-circle" class="w-5 h-5 text-success-500" />
                    @else
                        <x-filament::icon icon="heroicon-o-x-circle" class="w-5 h-5 text-gray-400" />
                    @endif
                    <span>شهادة الإنتاج (PCSID)</span>
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>