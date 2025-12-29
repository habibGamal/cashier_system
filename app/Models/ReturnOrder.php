<?php

namespace App\Models;

use App\Enums\ReturnOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
        'user_id',
        'shift_id',
        'return_number',
        'status',
        'refund_amount',
        'reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'refund_amount' => 'decimal:2',
            'status' => ReturnOrderStatus::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }

    // Computed attributes
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->items->sum('total');
    }
}
