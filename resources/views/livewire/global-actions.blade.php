<div>

    @if ($this->toCashierAction()->isVisible())
        {{ $this->toCashierAction() }}
    @endif

    @if ($this->openDayAction()->isVisible())
        {{ $this->openDayAction() }}
    @endif

    @if ($this->closeDayAction()->isVisible())
        {{ $this->closeDayAction() }}
    @endif

    <x-filament-actions::modals />
</div>
