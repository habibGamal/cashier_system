<?php

namespace App\Filament\Actions;

class PrintReturnPurchaseInvoiceAction extends PrintInvoiceAction
{
    protected static function getInvoiceType(): string
    {
        return 'return_purchase_invoice';
    }
}
