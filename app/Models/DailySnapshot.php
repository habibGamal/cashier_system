<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailySnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'total_sales',
        'total_expenses',
        'profit',
    ];
}
