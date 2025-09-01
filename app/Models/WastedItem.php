<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WastedItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'waste_id',
        'product_id',
        'quantity',
        'price',
        'total',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function waste()
    {
        return $this->belongsTo(Waste::class);
    }
}
