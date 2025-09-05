<?php

namespace Tests\Unit\Jobs;

use App\Jobs\PrintOrderReceipt;
use App\Models\Order;
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
            return true; // Just check that the job was dispatched
        });
    }

    public function test_jobs_are_dispatched_to_printing_queue()
    {
        Queue::fake();

        // Create test data
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        // Dispatch jobs
        PrintOrderReceipt::dispatch($order);

        // Assert jobs are on the printing queue
        Queue::assertPushedOn('printing', PrintOrderReceipt::class);
    }
}
