<?php

namespace App\Filament\Resources\Drivers\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\ViewAction;
use App\Models\Order;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Models\Shift;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'طلبات السائق';

    protected static ?string $label = 'طلب';

    protected static ?string $pluralLabel = 'الطلبات';

    protected $queryString = [
        'tableFilters',
    ];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->label('العميل')
                    ->searchable()
                    ->preload(),

                Select::make('type')
                    ->label('نوع الطلب')
                    ->options([
                        OrderType::DELIVERY->value => 'توصيل',
                        OrderType::TAKEAWAY->value => 'استلام',
                        OrderType::DINE_IN->value => 'تناول في المطعم',
                        OrderType::TALABAT->value => 'طلبات',
                        OrderType::WEB_DELIVERY->value => 'توصيل ويب',
                        OrderType::WEB_TAKEAWAY->value => 'استلام ويب',
                        OrderType::COMPANIES->value => 'شركات',
                    ])
                    ->enum(OrderType::class)
                    ->required(),

                Select::make('status')
                    ->label('حالة الطلب')
                    ->options([
                        OrderStatus::PENDING->value => 'في الانتظار',
                        OrderStatus::PROCESSING->value => 'قيد التحضير',
                        OrderStatus::OUT_FOR_DELIVERY->value => 'في الطريق',
                        OrderStatus::COMPLETED->value => 'مكتمل',
                        OrderStatus::CANCELLED->value => 'ملغي',
                    ])
                    ->enum(OrderStatus::class)
                    ->required(),

                TextInput::make('total')
                    ->label('إجمالي الطلب')
                    ->numeric()
                    ->prefix('ج.م')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_number')
            ->columns([
                TextColumn::make('order_number')
                    ->label('رقم الطلب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->default('عميل مؤقت'),

                TextColumn::make('type')
                    ->label('نوع الطلب')
                    ->badge(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),

                TextColumn::make('total')
                    ->label('إجمالي الطلب')
                    ->formatStateUsing(fn($state) => number_format($state, 2) . ' ج.م')
                    ->sortable(),

                TextColumn::make('shift.user.name')
                    ->label('المستخدم')
                    ->default('غير محدد'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('حالة الطلب')
                    ->options([
                        OrderStatus::PENDING->value => 'في الانتظار',
                        OrderStatus::PROCESSING->value => 'قيد التحضير',
                        OrderStatus::OUT_FOR_DELIVERY->value => 'في الطريق',
                        OrderStatus::COMPLETED->value => 'مكتمل',
                        OrderStatus::CANCELLED->value => 'ملغي',
                    ]),

                SelectFilter::make('type')
                    ->label('نوع الطلب')
                    ->options([
                        OrderType::DELIVERY->value => 'توصيل',
                        OrderType::TAKEAWAY->value => 'استلام',
                        OrderType::DINE_IN->value => 'تناول في المطعم',
                        OrderType::TALABAT->value => 'طلبات',
                        OrderType::WEB_DELIVERY->value => 'توصيل ويب',
                        OrderType::WEB_TAKEAWAY->value => 'استلام ويب',
                        OrderType::COMPANIES->value => 'شركات',
                    ]),
                SelectFilter::make('shift_ids')->label('الشفتات')
                    ->options(function () {
                        return Shift::with('user')
                            ->orderBy('start_at', 'desc')
                            ->get()
                            ->mapWithKeys(function ($shift) {
                                $userLabel = $shift->user ? $shift->user->name : 'غير محدد';
                                $startDate = $shift->start_at ? $shift->start_at->format('d/m/Y H:i') : 'غير محدد';
                                $endDate = $shift->end_at ? $shift->end_at->format('d/m/Y H:i') : 'لم ينته';

                                return [
                                    $shift->id => "شفت #{$shift->id} - {$userLabel} ({$startDate} - {$endDate})"
                                ];
                            });
                    })
                    ->searchable()
                    ->placeholder('اختر الشفتات')
                    ->multiple()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        $shiftIds = $data['values'] ?? [];
                        return $query->whereIn('shift_id', $shiftIds);
                    }),
                Filter::make('date_range')
                    ->label('فترة زمنية')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('من تاريخ'),
                        DatePicker::make('created_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'من: ' . Carbon::parse($data['created_from'])->format('d/m/Y');
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'إلى: ' . Carbon::parse($data['created_until'])->format('d/m/Y');
                        }

                        return $indicators;
                    }),
            ])
            ->headerActions([
                // No create action needed for orders in driver context
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                // No bulk actions needed
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    /**
     * Apply shift IDs filter if provided in the URL
     */
    public function mount(): void
    {
        parent::mount();

        $shiftIds = request()->get('shift_ids');
        if ($shiftIds) {
            // Apply the filter automatically when shift_ids is provided in URL
            $this->tableFilters = [
                'shift_ids' => [
                    'shift_ids' => $shiftIds
                ]
            ];
        }
    }

    /**
     * Modify the table query to apply filters when shift_ids is provided in URL
     */
    protected function applyFiltersToTableQuery(Builder $query): Builder
    {
        $query = parent::applyFiltersToTableQuery($query);

        // Check if shift_ids is provided in URL and apply the filter
        $shiftIds = request()->get('shift_ids');
        if ($shiftIds) {
            $shiftIdsArray = array_filter(
                array_map('trim', explode(',', $shiftIds)),
                fn($id) => is_numeric($id)
            );

            if (!empty($shiftIdsArray)) {
                $query->whereIn('shift_id', $shiftIdsArray);
            }
        }

        return $query;
    }
}
