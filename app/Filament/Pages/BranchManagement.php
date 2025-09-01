<?php

namespace App\Filament\Pages;

use Exception;
use App\Filament\Traits\AdminAccess;
use App\Services\BranchService;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class BranchManagement extends Page implements HasForms
{
    use InteractsWithForms, AdminAccess;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office';
    protected string $view = 'filament.pages.branch-management';
    protected static ?string $navigationLabel = 'إدارة الفروع';
    protected static ?string $title = 'إدارة الفروع';
    protected static string | \UnitEnum | null $navigationGroup = 'إدارة النظام';
    protected static ?int $navigationSort = 2;

    public $newProducts = [];
    public $changedPrices = [];
    public $changedRecipes = [];

    public function mount(): void
    {
        $branchService = app(BranchService::class);

        if (!$branchService->isSlave()) {
            redirect()->route('filament.admin.pages.settings')
                ->with('warning', 'هذه الصفحة متاحة فقط للفروع');
            return;
        }

        $this->loadNewProducts();
        $this->loadChangedPrices();
        $this->loadChangedRecipes();
    }

    public function loadNewProducts(): void
    {
        try {
            $branchService = app(BranchService::class);
            $this->newProducts =
            $branchService->getNewProductsFromMaster();
        } catch (Exception $e) {
            $this->newProducts = [];
            Notification::make()
                ->title('خطأ في تحميل المنتجات الجديدة')
                ->body('حدث خطأ أثناء محاولة تحميل المنتجات الجديدة من النقطة الرئيسية')
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
                ->send();
        }
    }

    public function loadChangedPrices(): void
    {
        try {
            $branchService = app(BranchService::class);
            $this->changedPrices = $branchService->getChangedPricesProductsFromMaster();
        } catch (Exception $e) {
            $this->changedPrices = [];
            Notification::make()
                ->title('خطأ في تحميل الأسعار المتغيرة')
                ->body('حدث خطأ أثناء محاولة تحميل الأسعار المتغيرة من النقطة الرئيسية')
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
                ->send();
        }
    }

    public function loadChangedRecipes(): void
    {
        try {
            $branchService = app(BranchService::class);
            $this->changedRecipes = $branchService->getChangedRecipesProductsFromMaster();
        } catch (Exception $e) {
            $this->changedRecipes = [];
            Notification::make()
                ->title('خطأ في تحميل الوصفات المتغيرة')
                ->body('حدث خطأ أثناء محاولة تحميل الوصفات المتغيرة من النقطة الرئيسية')
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
                ->send();
        }
    }

    public function refreshData(): void
    {
        Cache::forget('branch.new_products');
        Cache::forget('branch.changed_prices');
        Cache::forget('branch.changed_recipes');
        $this->loadNewProducts();
        $this->loadChangedPrices();
        $this->loadChangedRecipes();

        Notification::make()
            ->title('تم تحديث البيانات')
            ->body('تم تحديث بيانات المنتجات والأسعار والوصفات من النقطة الرئيسية')
            ->icon('heroicon-o-arrow-path')
            ->iconColor('success')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        $branchService = app(BranchService::class);

        if (!$branchService->isSlave()) {
            return [];
        }

        $newProductsCount = collect($this->newProducts)->sum(fn($category) => count($category['products']));
        $changedPricesCount = collect($this->changedPrices)->sum(fn($category) => count($category['products']));
        $changedRecipesCount = collect($this->changedRecipes)->sum(fn($category) => count($category['products']));

        return [
            Action::make('refresh')
                ->label('تحديث البيانات')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action('refreshData'),

            Action::make('importProducts')
                ->label("استيراد المنتجات ({$newProductsCount})")
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->visible($newProductsCount > 0)
                ->schema([
                    CheckboxList::make('productIds')
                        ->label('اختر المنتجات للاستيراد')
                        ->options($this->getProductOptions($this->newProducts))
                        ->bulkToggleable()
                        ->required()
                        ->columns(2)
                ])
                ->action(function (array $data) {
                    $this->importProducts($data['productIds']);
                }),

            Action::make('updatePrices')
                ->label("تحديث الأسعار ({$changedPricesCount})")
                ->icon('heroicon-o-currency-dollar')
                ->color('warning')
                ->visible($changedPricesCount > 0)
                ->schema([
                    CheckboxList::make('productIds')
                        ->label('اختر المنتجات لتحديث أسعارها')
                        ->options($this->getPriceOptions($this->changedPrices))
                        ->bulkToggleable()
                        ->required()
                        ->columns(2)
                ])
                ->action(function (array $data) {
                    $this->updatePrices($data['productIds']);
                }),

            Action::make('updateRecipes')
                ->label("تحديث الوصفات ({$changedRecipesCount})")
                ->icon('heroicon-o-squares-2x2')
                ->color('info')
                ->visible($changedRecipesCount > 0)
                ->schema([
                    CheckboxList::make('productIds')
                        ->label('اختر المنتجات لتحديث وصفاتها')
                        ->bulkToggleable()
                        ->options($this->getRecipeOptions($this->changedRecipes))
                        ->required()
                        ->columns(2)
                ])
                ->action(function (array $data) {
                    $this->updateRecipes($data['productIds']);
                }),
        ];
    }

    private function getProductOptions(array $categories): array
    {
        $options = [];
        foreach ($categories as $category) {
            foreach ($category['products'] as $product) {
                $options[$product['id']] = "{$category['name']} - {$product['name']}";
            }
        }
        return $options;
    }

    private function getPriceOptions(array $categories): array
    {
        $options = [];
        foreach ($categories as $category) {
            foreach ($category['products'] as $product) {
                $priceInfo = '';
                if (isset($product['price'])) {
                    $priceInfo .= "سعر: {$product['price']} جنيه";
                }
                if (isset($product['cost'])) {
                    $priceInfo .= $priceInfo ? " | كلفة: {$product['cost']} جنيه" : "كلفة: {$product['cost']} جنيه";
                }
                $options[$product['id']] = "{$category['name']} - {$product['name']}" . ($priceInfo ? " ({$priceInfo})" : '');
            }
        }
        return $options;
    }

    private function getRecipeOptions(array $categories): array
    {
        $options = [];
        foreach ($categories as $category) {
            foreach ($category['products'] as $product) {
                $componentsCount = isset($product['components']) ? count($product['components']) : 0;
                $options[$product['id']] = "{$category['name']} - {$product['name']} ({$componentsCount} مكون)";
            }
        }
        return $options;
    }

    public function importProducts(array $productIds): void
    {
        try {
            if (empty($productIds)) {
                Notification::make()
                    ->title('لم يتم اختيار أي منتجات')
                    ->body('يرجى اختيار منتج واحد على الأقل للاستيراد')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->iconColor('warning')
                    ->send();
                return;
            }

            $branchService = app(BranchService::class);
            $branchService->importProductsFromMaster($productIds);

            Notification::make()
                ->title('تم استيراد المنتجات بنجاح')
                ->body('تم استيراد ' . count($productIds) . ' منتج من النقطة الرئيسية')
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->send();

            // Refresh data
            $this->refreshData();

        } catch (Exception $e) {
            Notification::make()
                ->title('خطأ في استيراد المنتجات')
                ->body('حدث خطأ أثناء محاولة استيراد المنتجات: ' . $e->getMessage())
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
                ->send();
        }
    }

    public function updatePrices(array $productIds): void
    {
        try {
            if (empty($productIds)) {
                Notification::make()
                    ->title('لم يتم اختيار أي منتجات')
                    ->body('يرجى اختيار منتج واحد على الأقل لتحديث سعره')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->iconColor('warning')
                    ->send();
                return;
            }

            $branchService = app(BranchService::class);
            $branchService->updateProductPricesFromMaster($productIds);

            Notification::make()
                ->title('تم تحديث الأسعار بنجاح')
                ->body('تم تحديث أسعار ' . count($productIds) . ' منتج من النقطة الرئيسية')
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->send();

            // Refresh data
            $this->refreshData();

        } catch (Exception $e) {
            Notification::make()
                ->title('خطأ في تحديث الأسعار')
                ->body('حدث خطأ أثناء محاولة تحديث الأسعار: ' . $e->getMessage())
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
                ->send();
        }
    }

    public function updateRecipes(array $productIds): void
    {
        try {
            if (empty($productIds)) {
                Notification::make()
                    ->title('لم يتم اختيار أي منتجات')
                    ->body('يرجى اختيار منتج واحد على الأقل لتحديث وصفته')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->iconColor('warning')
                    ->send();
                return;
            }

            $branchService = app(BranchService::class);
            $branchService->updateProductRecipesFromMaster($productIds);

            Notification::make()
                ->title('تم تحديث الوصفات بنجاح')
                ->body('تم تحديث وصفات ' . count($productIds) . ' منتج من النقطة الرئيسية')
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->send();

            // Refresh data
            $this->refreshData();

        } catch (Exception $e) {
            Notification::make()
                ->title('خطأ في تحديث الوصفات')
                ->body('حدث خطأ أثناء محاولة تحديث الوصفات: ' . $e->getMessage())
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
                ->send();
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        $branchService = app(BranchService::class);
        return $branchService->isSlave();
    }
}
