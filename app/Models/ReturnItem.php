<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_order_id',
        'order_item_id',
        'product_id',
        'quantity',
        'original_price',
        'original_cost',
        'return_price',
        'total',
        'reason',
    ];

    protected $casts = [
        'quantity' => 'double',
        'original_price' => 'decimal:2',
        'original_cost' => 'decimal:2',
        'return_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function returnOrder(): BelongsTo
    {
        return $this->belongsTo(ReturnOrder::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
