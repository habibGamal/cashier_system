<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StocktakingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stocktaking_id',
        'product_id',
        'stock_quantity',
        'real_quantity',
        'price',
        'total',
    ];

    public function stocktaking()
    {
        return $this->belongsTo(Stocktaking::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
