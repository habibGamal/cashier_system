<?php

namespace App\Livewire;

use Filament\Notifications\Notification;
use Exception;
use Livewire\Component;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use App\Services\InventoryDailyAggregationService;


class GlobalActions extends Component implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    public function toCashierAction()
    {
        return Action::make('toCashier')
            ->label('الكاشير')
            ->icon('heroicon-o-computer-desktop')
            ->color('primary')
            ->url(route('orders.index'))
            ->visible(auth()->user()->isAdmin());
    }

    public function openDayAction()
    {

        $isDayClosed = app(InventoryDailyAggregationService::class)->dayStatus() === null;
        return Action::make('openDay')
            ->label('فتح اليوم')
            ->icon('heroicon-o-lock-open')
            ->color('success')
            ->visible($isDayClosed && auth()->user()->isAdmin())
            ->action(function () {
                $isDayClosed = app(InventoryDailyAggregationService::class)->dayStatus() === null;
                if (!$isDayClosed) {
                    Notification::make()
                        ->title('خطأ')
                        ->body('اليوم مفتوح بالفعل.')
                        ->danger()
                        ->send();
                    return;
                }
                app(InventoryDailyAggregationService::class)->openDay();
            });
    }

    public function closeDayAction()
    {
        $isDayClosed = app(InventoryDailyAggregationService::class)->dayStatus() === null;
        return Action::make('closeDay')
            ->label('إغلاق اليوم')
            ->icon('heroicon-o-lock-closed')
            ->color('danger')
            ->visible(!$isDayClosed && auth()->user()->isAdmin())
            ->action(function () {
                try {
                    $isDayClosed = app(InventoryDailyAggregationService::class)->dayStatus() === null;
                    if ($isDayClosed) {
                        throw new Exception('اليوم مغلق بالفعل.');
                    }
                    app(InventoryDailyAggregationService::class)->closeDay();
                } catch (Exception $e) {
                    Notification::make()
                        ->title('خطأ')
                        ->body('حدث خطأ أثناء إغلاق اليوم: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }


    public function render()
    {
        return view('livewire.global-actions');
    }
}
