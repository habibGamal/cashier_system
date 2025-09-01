<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class PrintInvoiceAction
{
    public static function make(?string $name = null): Action
    {
        return Action::make($name ?? 'print')
            ->label('طباعة')
            ->icon('heroicon-o-printer')
            ->color('success')
            ->url(function (Model $record, array $data) {
                $type = static::getInvoiceType();
                return route('invoice.print', ['type' => $type, 'id' => $record->id]);
            })
            ->openUrlInNewTab();
    }

    protected static function getInvoiceType(): string
    {
        return 'invoice';
    }

    public static function table(?string $name = null): Action
    {
        return Action::make($name ?? 'print')
            ->label('طباعة')
            ->icon('heroicon-o-printer')
            ->color('success')
            ->url(function (Model $record) {
                $type = static::getInvoiceType();
                return route('invoice.print', ['type' => $type, 'id' => $record->id]);
            })
            ->openUrlInNewTab();
    }
}
