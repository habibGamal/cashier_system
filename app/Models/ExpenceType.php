<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'avg_month_rate',
    ];

    protected $casts = [
        'avg_month_rate' => 'decimal:2',
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }


}
