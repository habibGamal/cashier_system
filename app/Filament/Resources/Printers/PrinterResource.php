<?php

namespace App\Filament\Resources\Printers;

use App\Filament\Resources\Printers\Schemas\PrinterForm;
use App\Filament\Resources\Printers\Tables\PrintersTable;
use Filament\Schemas\Schema;
use App\Filament\Resources\Printers\Pages\ListPrinters;
use App\Filament\Resources\Printers\Pages\CreatePrinter;
use App\Filament\Resources\Printers\Pages\ViewPrinter;
use App\Filament\Resources\Printers\Pages\EditPrinter;
use App\Models\Printer;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use App\Filament\Traits\AdminAccess;

class PrinterResource extends Resource
{
    use AdminAccess;

    protected static ?string $model = Printer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-printer';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الشركة';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'طابعة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الطابعات';
    }

    public static function form(Schema $schema): Schema
    {
        return PrinterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrintersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrinters::route('/'),
            'create' => CreatePrinter::route('/create'),
            'view' => ViewPrinter::route('/{record}'),
            'edit' => EditPrinter::route('/{record}/edit'),
        ];
    }
}
