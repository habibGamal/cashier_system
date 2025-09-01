<?php

namespace App\Filament\Actions;

class PrintStocktakingAction extends PrintInvoiceAction
{
    protected static function getInvoiceType(): string
    {
        return 'stocktaking';
    }
}
