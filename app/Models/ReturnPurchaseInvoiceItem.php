<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnPurchaseInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_purchase_invoice_id',
        'product_id',
        'product_name',
        'quantity',
        'price',
        'total',
    ];

    public function returnPurchaseInvoice()
    {
        return $this->belongsTo(ReturnPurchaseInvoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
