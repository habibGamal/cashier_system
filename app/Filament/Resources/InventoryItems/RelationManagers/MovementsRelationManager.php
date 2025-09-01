<?php

namespace App\Filament\Resources\InventoryItems\RelationManagers;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use App\Models\Order;
use App\Models\PurchaseInvoice;
use App\Models\ReturnPurchaseInvoice;
use App\Models\Waste;
use App\Models\Stocktaking;
use App\Enums\InventoryMovementOperation;
use App\Enums\MovementReason;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return 'حركات المخزون';
    }

    public static function getModelLabel(): string
    {
        return 'حركة مخزون';
    }

    public static function getPluralModelLabel(): string
    {
        return 'حركات المخزون';
    }

    protected $queryString = [
        'tableFilters',
    ];

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('operation')
                    ->label('نوع العملية')
                    ->badge(),
                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('السبب')
                    ->badge(),
                TextColumn::make('referenceable_type')
                    ->label('نوع المرجع')
                    ->badge(),
                TextColumn::make('referenceable_id')
                    ->label('رقم المرجع'),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('operation')
                    ->label('نوع العملية')
                    ->options(InventoryMovementOperation::class),
                SelectFilter::make('reason')
                    ->label('السبب')
                    ->options(MovementReason::class),
                Filter::make('created_at')
                    ->schema([
                        DateTimePicker::make('created_from'),
                        DateTimePicker::make('created_until')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->where('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->where('created_at', '<=', $date),
                            );
                    })
            ])
            ->recordActions([
                Action::make('view_reference')
                    ->label('عرض المرجع')
                    ->icon('heroicon-o-eye')
                    ->url(fn($record) => match ($record->referenceable_type) {
                        Order::class => route('filament.admin.resources.orders.view', ['record' => $record->referenceable_id]),
                        PurchaseInvoice::class => route('filament.admin.resources.purchase-invoices.view', ['record' => $record->referenceable_id]),
                        ReturnPurchaseInvoice::class => route('filament.admin.resources.return-purchase-invoices.view', ['record' => $record->referenceable_id]),
                        Waste::class => route('filament.admin.resources.wastes.view', ['record' => $record->referenceable_id]),
                        Stocktaking::class => route('filament.admin.resources.stocktakings.view', ['record' => $record->referenceable_id]),
                        default => null,
                    })
                    ->openUrlInNewTab()
                    ->color('primary'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
