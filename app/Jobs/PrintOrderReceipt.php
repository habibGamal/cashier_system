<?php

namespace App\Jobs;

use Exception;
use Throwable;
use App\Models\Order;
use App\Services\PrintService;
use App\Enums\SettingKey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PrintOrderReceipt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes timeout
    public $tries = 1; // Retry 3 times on failure

    /**
     * Create a new job instance.
     */
    public function __construct(
        private Order $order
    ) {
        // Set queue name for printing jobs
        $this->onQueue('printing');
    }

    /**
     * Execute the job.
     */
    public function handle(PrintService $printService): void
    {
        try {
            Log::info("Processing order receipt print job for order {$this->order->id}");

            $printService->printOrderViaBrowsershot($this->order);

            Log::info("Order receipt print job completed successfully for order {$this->order->id}");
        } catch (Exception $e) {
            Log::error("Order receipt print job failed for order {$this->order->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("Order receipt print job permanently failed for order {$this->order->id}: " . $exception->getMessage());
    }
}
