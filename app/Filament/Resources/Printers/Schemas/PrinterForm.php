<?php

namespace App\Filament\Resources\Printers\Schemas;

use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use App\Services\PrinterScanService;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class PrinterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم الطابعة')
                    ->required()
                    ->maxLength(255),

                Grid::make(2)
                    ->schema([
                        TextInput::make('ip_address')
                            ->label('عنوان IP')
                            ->helperText(
                                'أدخل عنوان IP بصيغة صحيحة أو //ip/printerName للطابعة المشتركة عبر USB'
                            )
                            ->maxLength(255),

                        Actions::make([
                            Action::make('scan_printers')
                                ->label('البحث عن الطابعات')
                                ->icon('heroicon-o-magnifying-glass')
                                ->color('info')
                                ->modalHeading('البحث عن الطابعات في الشبكة')
                                ->modalDescription('البحث عن الطابعات المتاحة في الشبكة المحلية')
                                ->modalSubmitActionLabel('تحديد الطابعة')
                                ->modalCancelActionLabel('إغلاق')
                                ->steps([
                                    Step::make('network_scan')
                                        ->label('البحث في الشبكة')
                                        ->description('أدخل نطاق الشبكة وابدأ البحث')
                                        ->schema([
                                            TextInput::make('network_range')
                                                ->label('نطاق الشبكة')
                                                ->default('192.168.1.0/24')
                                                ->required()
                                                ->helperText('أدخل نطاق الشبكة للبحث (مثال: 192.168.1.0/24)'),

                                            Actions::make([
                                                Action::make('start_scan')
                                                    ->label('بدء البحث')
                                                    ->icon('heroicon-o-magnifying-glass')
                                                    ->color('primary')
                                                    ->action(function (array $data, $livewire, $set, $get) {
                                                        $scanService = app(PrinterScanService::class);

                                                        if (! $scanService->isNmapAvailable()) {
                                                            Notification::make()
                                                                ->title('خطأ')
                                                                ->body('برنامج nmap غير متوفر على النظام')
                                                                ->danger()
                                                                ->send();

                                                            return;
                                                        }

                                                        try {
                                                            $printers = $scanService->scanNetworkForPrinters($data['network_range'] ?? '192.168.1.0/24');

                                                            $set('scan_results', $printers);

                                                            Notification::make()
                                                                ->title('تم البحث بنجاح')
                                                                ->body('تم العثور على '.count($printers).' طابعة محتملة')
                                                                ->success()
                                                                ->send();
                                                        } catch (Exception $e) {
                                                            Notification::make()
                                                                ->title('خطأ في البحث')
                                                                ->body('حدث خطأ أثناء البحث: '.$e->getMessage())
                                                                ->danger()
                                                                ->send();
                                                        }
                                                    }),
                                            ]),
                                        ]),

                                    Step::make('printer_selection')
                                        ->label('اختيار الطابعة')
                                        ->description('اختبر واختر الطابعة المناسبة')
                                        ->schema([
                                            Repeater::make('scan_results')
                                                ->label('الطابعات المكتشفة')
                                                ->defaultItems(0)
                                                ->schema([
                                                    TextInput::make('ip')
                                                        ->label('عنوان IP للطابعة')
                                                        ->required()
                                                        ->maxLength(255)
                                                        ->suffixAction(
                                                            Action::make('test_connection')
                                                                ->label('اختبار الاتصال')
                                                                ->icon('heroicon-o-link')
                                                                ->action(function ($state, $livewire, $set) {
                                                                    $scanService = app(PrinterScanService::class);
                                                                    try {
                                                                        $result = $scanService->testPrinter($state);
                                                                        $set('status', 'متصل');
                                                                        Notification::make()
                                                                            ->title('نجاح')
                                                                            ->body('تم الاتصال بالطابعة بنجاح: '.$state)
                                                                            ->success()
                                                                            ->send();
                                                                    } catch (Exception $e) {
                                                                        $set('status', 'غير متصل');
                                                                        Notification::make()
                                                                            ->title('فشل الاتصال')
                                                                            ->body('تعذر الاتصال بالطابعة: '.$e->getMessage())
                                                                            ->danger()
                                                                            ->send();
                                                                    }
                                                                })
                                                        ),
                                                    TextInput::make('status')
                                                        ->label('الحالة')
                                                        ->disabled(),
                                                    Actions::make([
                                                        Action::make('test_connection')
                                                            ->label('اختبار الاتصال')
                                                            ->icon('heroicon-o-link')
                                                            ->action(function ($state, $livewire, $set) {
                                                                $scanService = app(PrinterScanService::class);
                                                                try {
                                                                    $result = $scanService->testPrinter($state['ip']);
                                                                    $set('status', 'متصل');
                                                                    Notification::make()
                                                                        ->title('نجاح')
                                                                        ->body('تم الاتصال بالطابعة بنجاح: '.$state['ip'])
                                                                        ->success()
                                                                        ->send();
                                                                } catch (Exception $e) {
                                                                    $set('status', 'غير متصل');
                                                                    Notification::make()
                                                                        ->title('فشل الاتصال')
                                                                        ->body('تعذر الاتصال بالطابعة: '.$e->getMessage())
                                                                        ->danger()
                                                                        ->send();
                                                                }
                                                            }),
                                                        Action::make('select')
                                                            ->label('تحديد هذه الطابعة')
                                                            ->icon('heroicon-o-check')
                                                            ->action(function (array $state, $data, $get, $set) {
                                                                if (! empty($state['ip'])) {
                                                                    $set('../../selected_ip', $state['ip']);
                                                                    Notification::make()
                                                                        ->title('تم التحديد')
                                                                        ->body('تم تحديد الطابعة: '.$state['ip'])
                                                                        ->success()
                                                                        ->send();
                                                                } else {
                                                                    Notification::make()
                                                                        ->title('لم يتم التحديد')
                                                                        ->body('يرجى تحديد طابعة قبل المتابعة')
                                                                        ->warning()
                                                                        ->send();
                                                                }
                                                            }),
                                                    ]),
                                                ])
                                                ->columnSpanFull()
                                                ->columns(4)
                                                ->disableItemMovement()
                                                ->disableItemDeletion()
                                                ->disableItemCreation(),

                                            Hidden::make('selected_ip')
                                                ->reactive(),
                                        ]),
                                ])
                                ->action(function (array $data, $livewire, $set) {
                                    // Set the selected IP to the main form's ip_address field
                                    if (! empty($data['selected_ip'])) {
                                        $set('ip_address', $data['selected_ip']);

                                        Notification::make()
                                            ->title('تم التحديد')
                                            ->body('تم تحديد الطابعة: '.$data['selected_ip'])
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('لم يتم التحديد')
                                            ->body('يرجى تحديد طابعة قبل المتابعة')
                                            ->warning()
                                            ->send();
                                    }
                                }),
                        ])
                            ->alignEnd(),
                    ]),

                CheckboxList::make('categories')
                    ->label('اختر بالفئات ')
                    ->options(
                        Category::all()->pluck('name', 'id')
                    )
                    ->afterStateUpdated(function (array $state, callable $set) {
                        $set(
                            'products',
                            Product::whereIn('category_id', $state)
                                ->whereIn('type', [ProductType::Consumable, ProductType::Manufactured])
                                ->with('category')
                                ->orderBy('category_id')
                                ->pluck('id')
                                ->toArray()
                        );
                    })
                    ->bulkToggleable()
                    ->reactive()
                    ->dehydrated(false)
                    ->columns(3),

                CheckboxList::make('products')
                    ->label('المنتجات المرتبطة')
                    ->relationship(
                        'products',
                        'name',
                        function ($query) {
                            return $query->whereIn('type', [ProductType::Consumable, ProductType::Manufactured])
                                ->with('category')
                                ->orderBy('category_id');
                        }
                    )
                    ->getOptionLabelFromRecordUsing(fn (Product $product) => "$product->name ({$product->category?->name})")
                    ->bulkToggleable()
                    ->columns(3),
            ]);
    }
}
