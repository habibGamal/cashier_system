<?php

namespace App\Events\Orders;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $reason = ''
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('shift.' . $this->order->shift_id),
            new PrivateChannel('kitchen'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'order' => [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'reason' => $this->reason,
                'cancelled_at' => $this->order->updated_at->format('H:i:s'),
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.cancelled';
    }
}
