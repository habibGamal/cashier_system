<?php

namespace App\Filament\Actions;

class PrintPurchaseInvoiceAction extends PrintInvoiceAction
{
    protected static function getInvoiceType(): string
    {
        return 'purchase_invoice';
    }
}
