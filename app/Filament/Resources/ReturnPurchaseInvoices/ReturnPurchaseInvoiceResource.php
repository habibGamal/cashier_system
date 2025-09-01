<?php

namespace App\Filament\Resources\ReturnPurchaseInvoices;

use App\Filament\Resources\ReturnPurchaseInvoices\Schemas\ReturnPurchaseInvoiceForm;
use App\Filament\Resources\ReturnPurchaseInvoices\Schemas\ReturnPurchaseInvoiceInfolist;
use App\Filament\Resources\ReturnPurchaseInvoices\Tables\ReturnPurchaseInvoicesTable;
use Filament\Schemas\Schema;
use App\Filament\Resources\ReturnPurchaseInvoices\Pages\ListReturnPurchaseInvoices;
use App\Filament\Resources\ReturnPurchaseInvoices\Pages\CreateReturnPurchaseInvoice;
use App\Filament\Resources\ReturnPurchaseInvoices\Pages\ViewReturnPurchaseInvoice;
use App\Filament\Resources\ReturnPurchaseInvoices\Pages\EditReturnPurchaseInvoice;
use App\Models\ReturnPurchaseInvoice;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Traits\AdminAccess;

class ReturnPurchaseInvoiceResource extends Resource
{
    use AdminAccess;

    protected static ?string $model = ReturnPurchaseInvoice::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationLabel = 'مرتجع المشتريات';

    protected static ?string $modelLabel = 'مرتجع شراء';

    protected static ?string $pluralModelLabel = 'مرتجع المشتريات';

    protected static string | \UnitEnum | null $navigationGroup = 'المشتريات';

    public static function form(Schema $schema): Schema
    {
        return ReturnPurchaseInvoiceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReturnPurchaseInvoiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReturnPurchaseInvoicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReturnPurchaseInvoices::route('/'),
            'create' => CreateReturnPurchaseInvoice::route('/create'),
            'view' => ViewReturnPurchaseInvoice::route('/{record}'),
            'edit' => EditReturnPurchaseInvoice::route('/{record}/edit'),
        ];
    }
}
