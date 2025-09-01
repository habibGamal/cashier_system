<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
        'expence_type_id',
        'amount',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function expenceType()
    {
        return $this->belongsTo(ExpenceType::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
