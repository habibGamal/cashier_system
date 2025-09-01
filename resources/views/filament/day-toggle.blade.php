@php
    $inventoryService = app(\App\Services\InventoryDailyAggregationService::class);
    $dayStatus = $inventoryService->dayStatus();
    $isOpen = $dayStatus !== null;

@endphp

<x-filament::button
    tag="button"
    method="post"
    :color="$isOpen ? 'danger' : 'success'"
    :icon="$isOpen ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open'"
    {{-- href="{{ route('inventory.toggleDay') }}" --}}
    {{-- wire:click="{{ $inventoryService->openDay() }}" --}}
    onclick="fetch('{{ route('inventory.toggleDay') }}', {method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}}).then(() => location.reload());"
>
    {{ $isOpen ? 'إغلاق اليوم' : 'فتح اليوم' }}
</x-filament::button>
