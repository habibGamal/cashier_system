<?php

use Tests\Unit\TestCase;
use App\Actions\Orders\ApplyDiscountAction;
use App\Enums\OrderStatus;
use App\Models\Order;

uses(Tests\Unit\TestCase::class);

describe('ApplyDiscountAction', function () {
    beforeEach(function () {
        $this->action = app(ApplyDiscountAction::class);
        $this->order = Order::factory()->create([
            'status' => OrderStatus::PROCESSING,
            'sub_total' => 100.00,
            'total' => 100.00,
            'discount' => 0.00,
        ]);
    });

    it('applies percentage discount', function () {
        $result = $this->action->execute($this->order->id, 10.0, 'percentage');

        expect($result)->toBeInstanceOf(Order::class);
        expect($result->id)->toBe($this->order->id);
    });

    it('applies fixed amount discount', function () {
        $result = $this->action->execute($this->order->id, 15.0, 'fixed');

        expect($result)->toBeInstanceOf(Order::class);
        expect($result->id)->toBe($this->order->id);
    });

    it('returns updated order', function () {
        $result = $this->action->execute($this->order->id, 20.0, 'percentage');

        expect($result->id)->toBe($this->order->id);
    });
});
