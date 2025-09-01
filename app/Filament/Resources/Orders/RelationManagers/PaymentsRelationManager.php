<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use App\Enums\PaymentMethod;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'مدفوعات الطلب';

    protected static ?string $modelLabel = 'دفعة';

    protected static ?string $pluralModelLabel = 'المدفوعات';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // This is view-only, no form needed
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->columns([
                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('EGP')
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('method')
                    ->label('طريقة الدفع')
                    ->badge()
                    ->sortable(),

                TextColumn::make('shift.id')
                    ->label('رقم الوردية')
                    ->placeholder('غير محدد')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الدفع')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),

                TextColumn::make('id')
                    ->label('رقم المرجع')
                    ->prefix('#')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('method')
                    ->label('طريقة الدفع')
                    ->options(PaymentMethod::class),

                Filter::make('amount')
                    ->label('المبلغ')
                    ->schema([
                        TextInput::make('amount_from')
                            ->label('من')
                            ->numeric()
                            ->prefix('ج.م'),
                        TextInput::make('amount_to')
                            ->label('إلى')
                            ->numeric()
                            ->prefix('ج.م'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '<=', $amount),
                            );
                    }),

                Filter::make('created_at')
                    ->label('تاريخ الدفع')
                    ->schema([
                        DatePicker::make('paid_from')
                            ->label('من تاريخ'),
                        DatePicker::make('paid_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['paid_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['paid_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                // View-only: no create action
            ])
            ->recordActions([
                // View-only: no edit or delete actions
            ])
            ->toolbarActions([
                // View-only: no bulk actions
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('لا توجد مدفوعات')
            ->emptyStateDescription('لم يتم تسجيل أي مدفوعات لهذا الطلب بعد.')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->striped()
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
