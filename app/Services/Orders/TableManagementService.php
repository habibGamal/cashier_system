<?php

namespace App\Services\Orders;

use App\Exceptions\OrderException;
use App\Models\DineTable;

class TableManagementService
{
    public function validateTableAvailability(string $tableNumber): void
    {
        if (!$this->isTableAvailable($tableNumber)) {
            throw new OrderException('هذه الطاولة محجوزة');
        }
    }

    public function isTableAvailable(string $tableNumber): bool
    {
        $table = DineTable::where('table_number', $tableNumber)->first();
        return !$table || $table->order_id === null;
    }

    public function reserveTable(string $tableNumber, int $orderId): DineTable
    {
        $this->validateTableAvailability($tableNumber);

        $table = DineTable::firstOrCreate(['table_number' => $tableNumber]);
        $table->update(['order_id' => $orderId]);

        return $table;
    }

    public function freeTable(string $tableNumber): void
    {
        DineTable::where('table_number', $tableNumber)
            ->update(['order_id' => null]);
    }

    public function getAvailableTables(): array
    {
        return DineTable::whereNull('order_id')
            ->pluck('table_number')
            ->toArray();
    }

    public function getOccupiedTables(): array
    {
        return DineTable::whereNotNull('order_id')
            ->with('order')
            ->get()
            ->toArray();
    }
}
