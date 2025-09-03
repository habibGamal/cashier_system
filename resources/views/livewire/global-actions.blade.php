<div>
    {{-- <x-filament-actions::group :actions="[
        $this->toCashierAction,
        $this->openDayAction,
        $this->closeDayAction,
    ]" /> --}}
    @if ($this->toCashierAction->isVisible())
        {{ $this->toCashierAction }}
    @endif


    @if ($this->openDayAction->isVisible())
        {{ $this->openDayAction }}
    @endif

    @if ($this->closeDayAction->isVisible())
        {{ $this->closeDayAction }}
    @endif

    <x-filament-actions::modals />
</div>
