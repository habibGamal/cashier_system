<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'delivery_cost',
    ];

    protected $appends = ['deliveryCost'];

    protected $casts = [
        'delivery_cost' => 'decimal:2',
    ];

    public function getDeliveryCostAttribute()
    {
        return $this->attributes['delivery_cost'] ?? 0;
    }
}
