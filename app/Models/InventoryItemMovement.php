<?php

namespace App\Models;

use App\Enums\InventoryMovementOperation;
use App\Enums\MovementReason;
use App\Observers\InventoryItemMovementObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([InventoryItemMovementObserver::class])]
class InventoryItemMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'operation',
        'quantity',
        'reason',
        'notes',
        'referenceable_type',
        'referenceable_id',
    ];

    protected $casts = [
        'operation' => InventoryMovementOperation::class,
        'reason' => MovementReason::class,
        'quantity' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function referenceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeIncoming($query)
    {
        return $query->where('operation', InventoryMovementOperation::IN);
    }

    public function scopeOutgoing($query)
    {
        return $query->where('operation', InventoryMovementOperation::OUT);
    }

    public function scopeByReason($query, MovementReason $reason)
    {
        return $query->where('reason', $reason);
    }

    public function scopeByProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }
}
