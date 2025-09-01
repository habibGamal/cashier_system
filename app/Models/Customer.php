<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'has_whatsapp',
        'address',
        'region',
        'delivery_cost',
    ];

    protected $appends = ['hasWhatsapp', 'deliveryCost'];

    protected $casts = [
        'has_whatsapp' => 'boolean',
        'delivery_cost' => 'decimal:2',
    ];

    public function getHasWhatsappAttribute()
    {
        return $this->attributes['has_whatsapp'] ?? false;
    }

    public function getDeliveryCostAttribute()
    {
        return $this->attributes['delivery_cost'] ?? 0;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
