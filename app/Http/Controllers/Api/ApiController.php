<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Models\Product;
use App\Models\Category;
use App\Enums\ProductType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class ApiController extends Controller
{
    /**
     * Health check endpoint
     */
    public function check(): JsonResponse
    {
        return response()->json(['message' => 'turbo']);
    }

    /**
     * Search products by name
     */
    public function productSearch(Request $request)
    {
        $search = $request->get('search', '');

        $products = Product::select(['id', 'name', 'product_ref'])
            ->where('name', 'like', "%{$search}%")
            ->where('type', '!=', ProductType::RawMaterial)
            ->get();
        return response()->json([
            'products' => $products,
        ]);
    }

    /**
     * Validate products by refs
     */
    public function validateProducts(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'productsRefs' => 'required|array',
            'productsRefs.*.name' => 'required|string',
            'productsRefs.*.ref' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $productsRefs = $request->get('productsRefs');
        $refs = collect($productsRefs)->pluck('ref')->toArray();

        $products = Product::whereIn('product_ref', $refs)
            ->select(['name', 'product_ref'])
            ->get();

        $valid = count($productsRefs) === $products->count();

        if ($valid) {
            return response()->json(['valid' => true]);
        }

        $foundRefs = $products->pluck('product_ref')->toArray();
        $invalidProductsRefs = collect($productsRefs)
            ->filter(fn($productRef) => !in_array($productRef['ref'], $foundRefs))
            ->values();

        return response()->json([
            'valid' => false,
            'invalidProductsRefs' => $invalidProductsRefs,
        ]);
    }

    /**
     * Get all manufactured products
     */
    public function allProducts()
    {
        $products = Product::where('type', ProductType::Manufactured)
            ->with('category')
            ->get();

        return BaseResource::collection($products);
    }

    /**
     * Get all products grouped by category with refs
     */
    public function allProductsRefsMaster()
    {
        $categories = Category::select(['id', 'name'])
            ->with(['products' => function ($query) {
                $query->select(['id', 'name', 'product_ref', 'type', 'category_id']);
            }])
            ->get()
            ->map(function ($category) {
                $category->products = $category->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'productRef' => $product->product_ref ?? 'PROD_' . str_pad($product->id, 6, '0', STR_PAD_LEFT),
                        'type' => $product->type,
                    ];
                });
                return $category;
            });

        return BaseResource::collection($categories);
    }

    /**
     * Get all products grouped by category with prices
     */
    public function allProductsPricesMaster()
    {
        $categories = Category::select(['id', 'name'])
            ->with(['products' => function ($query) {
                $query->select(['id', 'name', 'product_ref', 'price', 'cost', 'type', 'category_id']);
            }])
            ->get();

        return BaseResource::collection($categories);
    }

    /**
     * Get all products grouped by category with recipes
     */
    public function allProductsRecipesMaster()
    {
        $categories = Category::select(['id', 'name'])
            ->with(['products' => function ($query) {
                $query->select(['id', 'name', 'product_ref', 'type', 'category_id'])
                    ->where('type', ProductType::Manufactured)
                    ->with(['components' => function ($subQuery) {
                        $subQuery->select(['products.id', 'products.name', 'products.product_ref', 'products.type'])
                            ->with('category:id,name');
                    }]);
            }])
            ->get()
            ->map(function ($category) {
                $products = $category->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'productRef' => $product->product_ref,
                        'componentsHash' => $product->components_hash,
                        'components' => $product->components->map(function ($component) {
                            return [
                                'id' => $component->id,
                                'name' => $component->name,
                                'productRef' => $component->product_ref,
                                'type' => $component->type,
                                'category' => $component->category,
                                'pivot' => [
                                    'quantity' => $component->pivot->quantity,
                                ],
                            ];
                        }),
                    ];
                });

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'products' => $products,
                ];
            });

        return BaseResource::collection($categories);
    }

    /**
     * Get products by IDs with details
     */
    public function getProductsMaster(Request $request)
    {
        $ids = $request->get('ids', []);
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        $products = Product::whereIn('id', $ids)
            ->with(['components.category', 'category'])
            ->get();

        return BaseResource::collection($products);
    }

    /**
     * Get products by refs
     */
    public function getProductsMasterByRefs(Request $request)
    {
        $refs = $request->get('refs', []);
        if (is_string($refs)) {
            $refs = explode(',', $refs);
        }

        $products = Product::whereIn('product_ref', $refs)->get();

        return BaseResource::collection($products);
    }

    /**
     * Get products prices by IDs
     */
    public function getProductsPricesMaster(Request $request)
    {
        $ids = $request->get('ids', []);
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        $products = Product::whereIn('id', $ids)
            ->select(['id', 'name', 'product_ref', 'type', 'price', 'cost'])
            ->get();

        return BaseResource::collection($products);
    }
}
