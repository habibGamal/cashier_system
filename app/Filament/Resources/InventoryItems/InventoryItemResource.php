<?php

namespace App\Filament\Resources\InventoryItems;

use App\Filament\Resources\InventoryItems\Pages\ListInventoryItems;
use App\Filament\Resources\InventoryItems\Pages\ViewInventoryItem;
use App\Filament\Resources\InventoryItems\RelationManagers\MovementsRelationManager;
use App\Filament\Resources\InventoryItems\Tables\InventoryItemsTable;
use App\Filament\Traits\AdminAccess;
use App\Models\InventoryItem;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class InventoryItemResource extends Resource
{
    use AdminAccess;

    protected static ?string $model = InventoryItem::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة المخزون';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'عنصر مخزون';
    }

    public static function getPluralModelLabel(): string
    {
        return 'عناصر المخزون';
    }

    public static function table(Table $table): Table
    {
        return InventoryItemsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات المنتج')
                    ->schema([
                        TextEntry::make('product.name')
                            ->label('اسم المنتج'),
                        TextEntry::make('product.category.name')
                            ->label('الفئة'),
                        TextEntry::make('product.unit')
                            ->label('الوحدة'),
                        TextEntry::make('product.cost')
                            ->label('التكلفة'),
                        TextEntry::make('product.type')
                            ->label('نوع المنتج'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            MovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryItems::route('/'),
            'view' => ViewInventoryItem::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canView($record): bool
    {
        return true;
    }
}
