<?php

use Tests\Unit\TestCase;
use App\Actions\Orders\LinkCustomerAction;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Customer;

uses(Tests\Unit\TestCase::class);

describe('LinkCustomerAction', function () {
    beforeEach(function () {
        $this->action = app(LinkCustomerAction::class);
        $this->order = Order::factory()->create([
            'status' => OrderStatus::PROCESSING,
            'customer_id' => null,
        ]);
        $this->customer = Customer::factory()->create();
    });

    it('links customer to order', function () {
        $result = $this->action->execute($this->order->id, $this->customer->id);

        expect($result)->toBeInstanceOf(Order::class)
            ->and($result->customer_id)->toBe($this->customer->id);
    });

    it('returns updated order', function () {
        $result = $this->action->execute($this->order->id, $this->customer->id);

        expect($result->id)->toBe($this->order->id);
    });

    it('can link different customers', function () {
        $customer2 = Customer::factory()->create();

        $result = $this->action->execute($this->order->id, $customer2->id);

        expect($result->customer_id)->toBe($customer2->id);
    });
});
