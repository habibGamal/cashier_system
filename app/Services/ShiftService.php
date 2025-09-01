<?php

namespace App\Services;

use Exception;
use App\Models\Shift;
use App\Models\User;
use App\Enums\PaymentMethod;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\DB;

class ShiftService
{
    /**
     * Get the current active shift for a user
     */
    public function getCurrentShift(): ?Shift
    {
        return Shift::where('end_at', null)
            ->where('closed', false)
            ->first();
    }

    /**
     * Start a new shift for a user
     */
    public function startShift(float $startCash, ?User $user = null): Shift
    {
        shouldDayBeOpen();

        // Check if user already has an active shift
        $activeShift = $this->getCurrentShift();
        if ($activeShift) {
            throw new Exception('User already has an active shift');
        }

        return DB::transaction(function () use ($user, $startCash) {
            return Shift::create([
                'start_cash' => $startCash,
                'start_at' => now(),
                'closed' => false,
                'user_id' => $user ? $user->id : auth()->id(),
            ]);
        });
    }

    /**
     * End the current shift for a user
     */
    public function endShift(float $realCash): Shift
    {

        $currentShift = $this->getCurrentShift();
        if (!$currentShift) {
            throw new Exception('No active shift found');
        }

        // Check if there are any orders in processing
        $processingOrders = $currentShift->orders()
            ->whereIn('status', [OrderStatus::PROCESSING, OrderStatus::OUT_FOR_DELIVERY, OrderStatus::PENDING])
            ->exists();

        if ($processingOrders) {
            throw new Exception('لا يمكن إنهاء الشيفت لوجود طلبات قيد المعالجة');
        }

        return DB::transaction(function () use ($currentShift, $realCash) {
            // Calculate end cash from shift orders
            $totalCash = $currentShift->orders()
                ->where('status', OrderStatus::COMPLETED)
                ->whereHas('payments', function ($query) {
                    $query->where('method', PaymentMethod::CASH);
                })
                ->sum('total');

            $endCash = $currentShift->start_cash + $totalCash;

            // Calculate if there's a deficit
            $deficit = $endCash - $realCash;
            $hasDeficit = $deficit > 0;

            $currentShift->update([
                'end_cash' => $endCash,
                'real_cash' => $realCash,
                'losses_amount' => $lossesAmount ?? 0,
                'has_deficit' => $hasDeficit,
                'end_at' => now(),
                'closed' => true,
            ]);

            return $currentShift->fresh();
        });
    }

    /**
     * Check if user can start a new shift
     */
    public function canStartShift(): bool
    {
        return $this->getCurrentShift() === null;
    }

    /**
     * Check if user can end current shift
     */
    public function canEndShift(): bool
    {
        return $this->getCurrentShift() !== null;
    }
}
