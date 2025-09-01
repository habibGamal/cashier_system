<?php

namespace App\Filament\Actions;

class PrintWasteAction extends PrintInvoiceAction
{
    protected static function getInvoiceType(): string
    {
        return 'waste';
    }
}
