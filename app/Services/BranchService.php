<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Enums\SettingKey;
use App\Enums\ProductType;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class BranchService
{
    private $querySettings;

    public function __construct(
        private SettingsService $settingsService
    ) {
        $this->querySettings = [
            'timeout' => 30,
            'verify' => false, // Similar to rejectUnauthorized: false
        ];
    }

    /**
     * Get the master node link from settings
     */
    public function getMasterNodeLink(): ?string
    {
        return $this->settingsService->get(SettingKey::MASTER_NODE_LINK->value);
    }

    /**
     * Get the current node type
     */
    public function getNodeType(): string
    {
        return $this->settingsService->get(SettingKey::NODE_TYPE->value, 'independent');
    }

    /**
     * Check if current node is a slave
     */
    public function isSlave(): bool
    {
        return $this->getNodeType() === 'slave';
    }

    /**
     * Test connection to master node
     */
    public function testMasterConnection(): bool
    {
        $masterLink = $this->getMasterNodeLink();
        if (!$masterLink) {
            return false;
        }

        try {
            $response = Http::withOptions($this->querySettings)
                ->get($masterLink . '/api/check');
            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Try to connect to master and execute callback
     */
    private function tryMasterConnection(callable $callback)
    {
        try {
            return $callback();
        } catch (Exception $e) {
            throw new Exception('لا يمكن الاتصال بالنقطة الرئيسية: ' . $e->getMessage());
        }
    }

    // API Query Methods

    private function queryAllProductsRefsMaster()
    {
        return $this->tryMasterConnection(function () {
            $masterLink = $this->getMasterNodeLink();
            return Http::withOptions($this->querySettings)
                ->get($masterLink . '/api/all-products-refs-master');
        });
    }

    private function queryAllProductsPricesMaster(): Response
    {
        return $this->tryMasterConnection(function () {
            $masterLink = $this->getMasterNodeLink();
            return Http::withOptions($this->querySettings)
                ->get($masterLink . '/api/all-products-prices-master');
        });
    }

    private function queryGetProductsMaster(string $ids)
    {
        return $this->tryMasterConnection(function () use ($ids) {
            $masterLink = $this->getMasterNodeLink();
            return Http::withOptions($this->querySettings)
                ->get($masterLink . '/api/get-products-master?ids=,' . $ids);
        });
    }

    private function queryGetProductsPricesMaster(string $ids)
    {
        return $this->tryMasterConnection(function () use ($ids) {
            $masterLink = $this->getMasterNodeLink();
            return Http::withOptions($this->querySettings)
                ->get($masterLink . '/api/get-products-prices-master?ids=,' . $ids);
        });
    }

    private function queryAllProductsRecipesMaster(): Response
    {
        return $this->tryMasterConnection(function () {
            $masterLink = $this->getMasterNodeLink();
            return Http::withOptions($this->querySettings)
                ->get($masterLink . '/api/all-products-recipes-master');
        });
    }

    // Main Methods - Following the original SlaveService logic

    /**
     * Get new products available from master that don't exist locally
     */
    public function getNewProductsFromMaster(): array
    {
        $response = $this->queryAllProductsRefsMaster();
        if (!$response->successful()) {
            throw new Exception('Failed to get products from master');
        }

        $allMasterProducts = $response->json();
        $productRefs = collect($allMasterProducts)
            ->flatMap(fn($category) => $category['products'])
            ->pluck('productRef')
            ->toArray();
        if (empty($productRefs)) {
            return [];
        }

        // Find products that don't exist locally
        $existingRefs = Product::whereIn('product_ref', $productRefs)
            ->pluck('product_ref')
            ->toArray();

        $nonExistingProducts = array_diff($productRefs, $existingRefs);
        // Filter master categories to only include new products
        return collect($allMasterProducts)->map(function ($category) use ($nonExistingProducts) {
            $filteredProducts = collect($category['products'])
                ->filter(fn($product) => in_array($product['productRef'], $nonExistingProducts))
                ->values()
                ->toArray();

            return [
                'id' => $category['id'],
                'name' => $category['name'],
                'products' => $filteredProducts,
            ];
        })->filter(fn($category) => !empty($category['products']))->values()->toArray();
    }

    /**
     * Get products with changed prices from master
     */
    public function getChangedPricesProductsFromMaster(): array
    {
        $response = $this->queryAllProductsPricesMaster();
        if (!$response->successful()) {
            throw new Exception('Failed to get prices from master');
        }

        $allMasterProducts = $response->json();
        $products = collect($allMasterProducts)->flatMap(fn($category) => $category['products']);
        $productsRefs = $products->pluck('productRef')->toArray();

        if (empty($productsRefs)) {
            return [];
        }

        // Find products with different prices
        $localProducts = Product::whereIn('product_ref', $productsRefs)
            ->get()
            ->keyBy('product_ref');

        $changedPricesProducts = $products->filter(function ($masterProduct) use ($localProducts) {
            $localProduct = $localProducts->get($masterProduct['productRef']);
            if (!$localProduct)
                return false;

            $isRawProduct = $localProduct->type === ProductType::RawMaterial;
            if ($isRawProduct)
                return false;

            $priceChanged = isset($masterProduct['price']) &&
                abs((float) $localProduct->price - (float) $masterProduct['price']) > 0.01;
            // $costChanged = isset($masterProduct['cost']) &&
            //     abs((float) $localProduct->cost - (float) $masterProduct['cost']) > 0.01;

            return $priceChanged;
        })->pluck('productRef')->toArray();

        // Filter master categories to only include products with changed prices
        return collect($allMasterProducts)->map(function ($category) use ($changedPricesProducts) {
            $filteredProducts = collect($category['products'])
                ->filter(fn($product) => in_array($product['productRef'], $changedPricesProducts))
                ->values()
                ->toArray();

            return [
                'id' => $category['id'],
                'name' => $category['name'],
                'products' => $filteredProducts,
            ];
        })->filter(fn($category) => !empty($category['products']))->values()->toArray();
    }

    /**
     * Get products with changed recipes from master
     */
    public function getChangedRecipesProductsFromMaster(): array
    {
        $response = $this->queryAllProductsRecipesMaster();
        if (!$response->successful()) {
            throw new Exception('Failed to get recipes from master');
        }

        $allMasterProducts = $response->json();
        $products = collect($allMasterProducts)->flatMap(fn($category) => $category['products']);
        $productsRefs = $products->pluck('productRef')->toArray();

        if (empty($productsRefs)) {
            return [];
        }

        // Find products with different recipes (components hash)
        $localProducts = Product::whereIn('product_ref', $productsRefs)
            ->with('components')
            ->get()
            ->keyBy('product_ref');

        $changedRecipesProducts = $products->filter(function ($masterProduct) use ($localProducts) {
            $localProduct = $localProducts->get($masterProduct['productRef']);
            if (!$localProduct) {
                return false;
            }

            // Compare components hash
            $masterComponentsHash = $masterProduct['componentsHash'] ?? '';
            $localComponentsHash = $localProduct->components_hash;

            return $masterComponentsHash !== $localComponentsHash;
        })->pluck('productRef')->toArray();

        // Filter master categories to only include products with changed recipes
        return collect($allMasterProducts)->map(function ($category) use ($changedRecipesProducts) {
            $filteredProducts = collect($category['products'])
                ->filter(fn($product) => in_array($product['productRef'], $changedRecipesProducts))
                ->values()
                ->toArray();

            return [
                'id' => $category['id'],
                'name' => $category['name'],
                'products' => $filteredProducts,
            ];
        })->filter(fn($category) => !empty($category['products']))->values()->toArray();
    }

    /**
     * Import products from master by IDs
     */
    public function importProductsFromMaster(array $productIds): bool
    {
        if (!$this->isSlave()) {
            throw new Exception('Only slave nodes can import products from master');
        }

        if (empty($productIds)) {
            throw new Exception('No products selected for import');
        }

        $ids = implode(',', $productIds);
        $response = $this->queryGetProductsMaster($ids);
        if (!$response->successful()) {
            throw new Exception('Failed to get product details from master');
        }

        $products = $response->json();

        DB::beginTransaction();
        try {
            // Extract all components (raw products) from manufactured products
            $rawProducts = collect($products)
                ->flatMap(fn($product) => $product['components'] ?? [])
                ->unique('productRef');

            // Create categories if they don't exist
            $allCategoryNames = collect($products)
                ->merge($rawProducts)
                ->pluck('category.name')
                ->unique()
                ->filter()
                ->toArray();

            foreach ($allCategoryNames as $categoryName) {
                Category::firstOrCreate(['name' => $categoryName]);
            }

            $localCategories = Category::whereIn('name', $allCategoryNames)
                ->get()
                ->keyBy('name');

            // Create raw products first
            foreach ($rawProducts as $rawProduct) {
                $localCategory = $localCategories->get($rawProduct['category']['name']);
                if ($localCategory) {
                    if ($rawProduct['type'] === 'manifactured') {
                        logger()->error('Raw product type is "manifactured", which is not valid for raw materials.', [
                            'data' => $rawProduct,
                        ]);
                        continue; // Skip invalid raw product types
                    }
                    Product::updateOrCreate(
                        ['product_ref' => $rawProduct['productRef']],
                        [
                            'name' => $rawProduct['name'],
                            'price' => $rawProduct['price'] ?? 0,
                            'cost' => $rawProduct['cost'] ?? 0,
                            'unit' => $rawProduct['unit'] ?? 'piece',
                            'category_id' => $localCategory->id,
                            'type' => $rawProduct['type'] ?? ProductType::RawMaterial,
                            'product_ref' => $rawProduct['productRef'],
                        ]
                    );
                }
            }

            // dd($products);
            // Create main products
            foreach ($products as $productData) {
                $localCategory = $localCategories->get($productData['category']['name']);
                $type = match ($productData['type']) {
                    ProductType::Manufactured->value, 'manifactured' => ProductType::Manufactured,
                    ProductType::RawMaterial->value => ProductType::RawMaterial,
                    ProductType::Consumable->value => ProductType::Consumable,
                    default => throw new Exception('نوع المنتج غير معروف: ' . $productData['type']),
                };
                if ($localCategory && $type === ProductType::RawMaterial) {
                    $product = Product::updateOrCreate(
                        ['product_ref' => $productData['productRef']],
                        [
                            'name' => $productData['name'],
                            'price' => $productData['price'] ?? 0,
                            'cost' => $productData['cost'] ?? 0,
                            'unit' => $productData['unit'] ?? 'piece',
                            'category_id' => $localCategory->id,
                            'type' => $type,
                            'product_ref' => $productData['productRef'],
                        ]
                    );
                } else if ($localCategory && $type === ProductType::Consumable) {
                    $product = Product::updateOrCreate(
                        ['product_ref' => $productData['productRef']],
                        [
                            'name' => $productData['name'],
                            'price' => $productData['price'] ?? 0,
                            'cost' => $productData['cost'] ?? 0,
                            'unit' => $productData['unit'] ?? 'piece',
                            'category_id' => $localCategory->id,
                            'type' => $type,
                            'product_ref' => $productData['productRef'],
                        ]
                    );
                } else if ($localCategory && $type === ProductType::Manufactured) {

                    $product = Product::updateOrCreate(
                        ['product_ref' => $productData['productRef']],
                        [
                            'name' => $productData['name'],
                            'price' => $productData['price'] ?? 0,
                            'cost' => $productData['cost'] ?? 0,
                            'unit' => $productData['unit'] ?? 'piece',
                            'category_id' => $localCategory->id,
                            'type' => $type,
                            'product_ref' => $productData['productRef'],
                        ]
                    );

                    // Handle product components for manufactured products
                    if (
                        $type === ProductType::Manufactured
                        && !empty($productData['components'])
                    ) {

                        // First, delete existing components
                        $product->components()->detach();

                        // Add new components
                        foreach ($productData['components'] as $component) {
                            $componentProduct = Product::where('product_ref', $component['productRef'])->first();
                            if ($componentProduct) {
                                $product->components()->attach($componentProduct->id, [
                                    'quantity' => $component['meta']['pivot_quantity'],
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to import products from master: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update product prices from master by IDs
     */
    public function updateProductPricesFromMaster(array $productIds): bool
    {
        if (!$this->isSlave()) {
            throw new Exception('Only slave nodes can update prices from master');
        }

        if (empty($productIds)) {
            throw new Exception('No products selected for price update');
        }

        $ids = implode(',', $productIds);
        $response = $this->queryGetProductsPricesMaster($ids);

        if (!$response->successful()) {
            throw new Exception('Failed to get product prices from master');
        }

        $products = $response->json();

        DB::beginTransaction();
        try {
            foreach ($products as $productData) {
                $localProduct = Product::where('product_ref', $productData['productRef'])->first();

                if ($localProduct) {
                    $updateData = [];

                    // Update based on product type (following original logic)
                    switch ($productData['type']) {
                        // for legacy compatibility
                        case 'manifactured':
                            if (isset($productData['price'])) {
                                $updateData['price'] = $productData['price'];
                            }
                            break;
                        case ProductType::Manufactured->value:
                            if (isset($productData['price'])) {
                                $updateData['price'] = $productData['price'];
                            }
                            break;

                        case ProductType::Consumable->value:
                            if (isset($productData['price'])) {
                                $updateData['price'] = $productData['price'];
                            }
                            break;
                    }

                    if (!empty($updateData)) {
                        $localProduct->update($updateData);
                    }
                }
            }

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update prices from master: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update product recipes from master by IDs
     */
    public function updateProductRecipesFromMaster(array $productIds): bool
    {
        if (!$this->isSlave()) {
            throw new Exception('Only slave nodes can update recipes from master');
        }

        if (empty($productIds)) {
            throw new Exception('No products selected for recipe update');
        }

        $ids = implode(',', $productIds);
        $response = $this->queryGetProductsMaster($ids);

        if (!$response->successful()) {
            throw new Exception('Failed to get product recipes from master');
        }

        $products = $response->json();

        DB::beginTransaction();
        try {
            // Extract all raw materials/components from the recipes
            $rawProducts = collect($products)
                ->flatMap(fn($product) => $product['components'] ?? [])
                ->unique('productRef');

            // Create categories if they don't exist
            $allCategoryNames = $rawProducts
                ->pluck('category.name')
                ->unique()
                ->filter()
                ->toArray();

            foreach ($allCategoryNames as $categoryName) {
                Category::firstOrCreate(['name' => $categoryName]);
            }

            $localCategories = Category::whereIn('name', $allCategoryNames)
                ->get()
                ->keyBy('name');

            // Create/update raw products first
            foreach ($rawProducts as $rawProduct) {
                $localCategory = $localCategories->get($rawProduct['category']['name']);
                if ($localCategory) {
                    Product::updateOrCreate(
                        ['product_ref' => $rawProduct['productRef']],
                        [
                            'name' => $rawProduct['name'],
                            'price' => $rawProduct['price'] ?? 0,
                            'cost' => $rawProduct['cost'] ?? 0,
                            'unit' => $rawProduct['unit'] ?? 'piece',
                            'category_id' => $localCategory->id,
                            'type' => $rawProduct['type'] ?? ProductType::RawMaterial,
                            'product_ref' => $rawProduct['productRef'],
                        ]
                    );
                }
            }

            // Update product recipes
            foreach ($products as $productData) {
                $localProduct = Product::where('product_ref', $productData['productRef'])->first();

                if ($localProduct && $productData['type'] === ProductType::Manufactured->value) {
                    // Clear existing components
                    $localProduct->components()->detach();

                    // Add new components from master
                    if (!empty($productData['components'])) {
                        foreach ($productData['components'] as $component) {
                            $componentProduct = Product::where('product_ref', $component['productRef'])->first();
                            if ($componentProduct) {
                                $localProduct->components()->attach($componentProduct->id, [
                                    'quantity' => $component['pivot']['quantity'] ?? 1,
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update recipes from master: ' . $e->getMessage());
            throw $e;
        }
    }

    // Convenience methods for the Settings page

    /**
     * Pull products from master node for selected categories
     * This method fetches new products and returns them for user selection
     */
    public function pullProductsFromMaster(array $categoryIds): array
    {
        if (!$this->isSlave()) {
            throw new Exception('Only slave nodes can pull products from master');
        }

        $newProducts = $this->getNewProductsFromMaster();

        // Filter by selected categories
        $filteredProducts = collect($newProducts)->filter(function ($category) use ($categoryIds) {
            return in_array($category['id'], $categoryIds);
        })->values()->toArray();

        return $filteredProducts;
    }

    /**
     * Pull prices from master node for selected categories
     * This method fetches changed prices and returns them for user selection
     */
    public function pullPricesFromMaster(array $categoryIds): array
    {
        if (!$this->isSlave()) {
            throw new Exception('Only slave nodes can pull prices from master');
        }

        $changedPrices = $this->getChangedPricesProductsFromMaster();

        // Filter by selected categories
        $filteredPrices = collect($changedPrices)->filter(function ($category) use ($categoryIds) {
            return in_array($category['id'], $categoryIds);
        })->values()->toArray();

        return $filteredPrices;
    }

    /**
     * Pull recipes from master node for selected categories
     * This method fetches changed recipes and returns them for user selection
     */
    public function pullRecipesFromMaster(array $categoryIds): array
    {
        if (!$this->isSlave()) {
            throw new Exception('Only slave nodes can pull recipes from master');
        }

        $changedRecipes = $this->getChangedRecipesProductsFromMaster();

        // Filter by selected categories
        $filteredRecipes = collect($changedRecipes)->filter(function ($category) use ($categoryIds) {
            return in_array($category['id'], $categoryIds);
        })->values()->toArray();

        return $filteredRecipes;
    }
}
