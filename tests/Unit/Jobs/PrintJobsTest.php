<?php

namespace Tests\Unit\Jobs;

use App\Jobs\PrintKitchenOrder;
use App\Jobs\PrintOrderReceipt;
use App\Models\Order;
use App\Models\Printer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PrintJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_order_receipt_job_is_dispatched()
    {
        Queue::fake();

        // Create test order
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        // Dispatch job
        PrintOrderReceipt::dispatch($order);

        // Assert job was dispatched
        Queue::assertPushed(PrintOrderReceipt::class, function ($job) use ($order) {
            return $job->order->id === $order->id;
        });
    }

    public function test_print_kitchen_order_job_is_dispatched()
    {
        Queue::fake();

        // Create test data
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $printer = Printer::factory()->create();
        $orderItems = [
            [
                'product_id' => 1,
                'quantity' => 2,
                'name' => 'Test Product',
                'notes' => 'Test notes'
            ]
        ];

        // Dispatch job
        PrintKitchenOrder::dispatch($order, $orderItems, $printer->id);

        // Assert job was dispatched
        Queue::assertPushed(PrintKitchenOrder::class, function ($job) use ($order, $printer) {
            return $job->order->id === $order->id &&
                   $job->printerId === $printer->id;
        });
    }

    public function test_jobs_are_dispatched_to_printing_queue()
    {
        Queue::fake();

        // Create test data
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $printer = Printer::factory()->create();

        // Dispatch jobs
        PrintOrderReceipt::dispatch($order);
        PrintKitchenOrder::dispatch($order, [], $printer->id);

        // Assert jobs are on the printing queue
        Queue::assertPushedOn('printing', PrintOrderReceipt::class);
        Queue::assertPushedOn('printing', PrintKitchenOrder::class);
    }
}
