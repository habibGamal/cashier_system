<?php

namespace App\Events\Orders;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebOrderReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order $order
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('web-orders'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'order' => [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'type' => $this->order->type->label(),
                'typeString' => $this->order->type->label(), // For compatibility with frontend
                'customer_name' => $this->order->customer->name,
                'customer_phone' => $this->order->customer->phone,
                'customer_address' => $this->order->customer->address,
                'total' => $this->order->total,
                'status' => $this->order->status->label(),
                'created_at' => $this->order->created_at->format('H:i:s'),
                'order_notes' => $this->order->order_notes,
                'items_count' => $this->order->items->count(),
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'web-order.received';
    }
}
