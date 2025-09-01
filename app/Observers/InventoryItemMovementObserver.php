<?php

namespace App\Observers;

use App\Models\InventoryItemMovement;

class InventoryItemMovementObserver
{
    /**
     * Handle the InventoryItemMovement "created" event.
     */
    public function created(InventoryItemMovement $inventoryItemMovement): void
    {
        // Observer logic here
        // dd('InventoryItemMovement created', $inventoryItemMovement);
    }

    /**
     * Handle the InventoryItemMovement "updated" event.
     */
    public function updated(InventoryItemMovement $inventoryItemMovement): void
    {
        //
    }

    /**
     * Handle the InventoryItemMovement "deleted" event.
     */
    public function deleted(InventoryItemMovement $inventoryItemMovement): void
    {
        //
    }

    /**
     * Handle the InventoryItemMovement "restored" event.
     */
    public function restored(InventoryItemMovement $inventoryItemMovement): void
    {
        //
    }

    /**
     * Handle the InventoryItemMovement "force deleted" event.
     */
    public function forceDeleted(InventoryItemMovement $inventoryItemMovement): void
    {
        //
    }
}
