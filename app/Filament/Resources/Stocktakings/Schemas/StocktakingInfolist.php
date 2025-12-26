<?php

namespace App\Filament\Resources\Stocktakings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StocktakingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الجرد')
                    ->schema([
                        TextEntry::make('id')
                            ->label('رقم الجرد'),

                        TextEntry::make('user.name')
                            ->label('المستخدم'),

                        TextEntry::make('total')
                            ->label('إجمالي الفرق')
                            ->money(currency_code()),

                        TextEntry::make('closed_at')
                            ->label('الحالة')
                            ->formatStateUsing(function ($state) {
                                return $state ? 'مغلق' : 'مفتوح';
                            })
                            ->badge()
                            ->color(fn ($state): string => $state ? 'success' : 'warning'),

                        TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('notes')
                            ->label('ملاحظات'),
                    ])
                    ->columns(3),
            ]);
    }
}
