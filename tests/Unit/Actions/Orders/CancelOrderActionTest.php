<?php

use Tests\Unit\TestCase;
use App\Actions\Orders\CancelOrderAction;
use App\Enums\OrderStatus;
use App\Models\Order;

uses(Tests\Unit\TestCase::class);

describe('CancelOrderAction', function () {
    beforeEach(function () {
        $this->action = app(CancelOrderAction::class);
        $this->order = Order::factory()->create([
            'status' => OrderStatus::PROCESSING,
        ]);
    });

    it('cancels order without reason', function () {
        $result = $this->action->execute($this->order->id);

        expect($result)->toBeInstanceOf(Order::class);
        expect($result->id)->toBe($this->order->id);
    });

    it('cancels order with reason', function () {
        $reason = 'Customer changed mind';

        $result = $this->action->execute($this->order->id, $reason);

        expect($result)->toBeInstanceOf(Order::class);
        expect($result->id)->toBe($this->order->id);
    });

    it('returns updated order', function () {
        $result = $this->action->execute($this->order->id, 'Test reason');

        expect($result->id)->toBe($this->order->id);
    });
});
