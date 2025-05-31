<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 *     name="Product Categories",
 *     description="API Endpoints for managing product categories relationships"
 * )
 */
class ProductCategoryApiController extends Controller
{
    /**
     * Get all categories for a product
     * 
     * @OA\Get(
     *     path="/api/products/{productId}/categories",
     *     summary="Get all categories for a product",
     *     description="Returns a list of categories associated with a specific product",
     *     operationId="getProductCategories",
     *     tags={"Product Categories"},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         description="ID of product to get categories for",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of product categories",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="name", type="string", example="Hair Care")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product not found")
     *         )
     *     )
     * )
     */
    public function index(Request $request, $productId)
    {
        // Find the product by ID
        $product = Product::find($productId);
        
        // Return 404 if product not found
        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }
        
        // Return categories with only id and name
        return $product->categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name
            ];
        });
    }

    /**
     * Sync categories for a product (replace all existing with the provided set)
     * 
     * @OA\Put(
     *     path="/api/products/{productId}/categories/sync",
     *     summary="Sync categories for a product",
     *     description="Replace all current categories associated with a product with the provided set. Send empty array to remove all categories.",
     *     operationId="syncProductCategories",
     *     tags={"Product Categories"},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         description="ID of product to sync categories for",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Array of category IDs to associate with the product",
     *         @OA\JsonContent(
     *             required={"categories"},
     *             @OA\Property(
     *                 property="categories",
     *                 type="array",
     *                 description="Array of category IDs. Send empty array [] to remove all categories.",
     *                 @OA\Items(type="string", format="uuid"),
     *                 example={"123e4567-e89b-12d3-a456-426614174000", "123e4567-e89b-12d3-a456-426614174001"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categories synced successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Categories synced successfully"),
     *             @OA\Property(
     *                 property="product",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="name", type="string", example="Hair Shampoo"),
     *                 @OA\Property(property="description", type="string", example="Professional hair shampoo"),
     *                 @OA\Property(property="price", type="number", format="float", example=19.99),
     *                 @OA\Property(property="stock", type="integer", example=50),
     *                 @OA\Property(
     *                     property="categories",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                         @OA\Property(property="name", type="string", example="Hair Care")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Missing or invalid fields",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The categories field is required"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "categories": {"The categories field is required"},
     *                     "categories.0": {"The selected categories.0 is invalid."}
     *                 }
     *             )
     *         )
     *     )
     * )
     */
    public function sync(Request $request, $productId)
    {
        // Find the product by ID
        $product = Product::find($productId);
        
        // Return 404 if product not found
        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }
        
        // Check if categories field exists in the request
        if (!$request->has('categories')) {
            return response()->json([
                'message' => 'The categories field is required',
                'errors' => [
                    'categories' => ['The categories field is required']
                ]
            ], 422);
        }
        
        // Validate that the request contains an array field
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'categories' => 'present|array',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Only validate category IDs if array is not empty
        if (count($request->categories) > 0) {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'categories.*' => 'exists:categories,id'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'One or more category IDs are invalid',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Convert the array of category IDs into a collection with pivot data
            // For each category ID, we set 'active' => 1 in the pivot table
            $categories = collect($request->categories)->mapWithKeys(function ($categoryId) {
                return [$categoryId => ['active' => 1]];
            });
        } else {
            // Empty array means we want to remove all categories
            $categories = [];
        }

        // The sync method:
        // 1. Detaches all categories not in the provided array
        // 2. Attaches any new categories from the array
        // 3. Updates pivot data for categories that remain
        $product->categories()->sync($categories);

        // Load the categories with only id and name fields
        $product->load(['categories' => function($query) {
            $query->select('categories.id', 'categories.name');
        }]);
        
        // Format the response to only include necessary fields
        $formattedProduct = [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'stock' => $product->stock,
            'categories' => $product->categories->map(function($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name
                ];
            })
        ];

        // Return a success response with the updated product and its categories
        return response()->json([
            'message' => 'Categories synced successfully',
            'product' => $formattedProduct
        ]);
    }

    /**
     * Get all products with their associated categories
     * 
     * @OA\Get(
     *     path="/api/products-with-categories",
     *     summary="Get all products with their associated categories",
     *     description="Returns a list of all products with their associated categories (id and name only)",
     *     operationId="getProductsWithCategories",
     *     tags={"Product Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="List of products with their categories",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="name", type="string", example="Hair Shampoo"),
     *                 @OA\Property(
     *                     property="categories",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                         @OA\Property(property="name", type="string", example="Hair Care")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function productsWithCategories()
    {
        // Get products with their categories but only select id and name fields
        $products = Product::with(['categories' => function($query) {
            $query->select(['categories.id', 'categories.name']);
        }])->get(['id', 'name']);
        
        // Transform the data to include only what's needed
        $formattedProducts = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'categories' => $product->categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name
                    ];
                })
            ];
        });
        
        return response()->json($formattedProducts);
    }
    
    /**
     * Get all categories that are associated with any products
     * 
     * @OA\Get(
     *     path="/api/product-categories",
     *     summary="Get all categories used by products",
     *     description="Returns a list of category names that are associated with at least one product",
     *     operationId="getUsedProductCategories",
     *     tags={"Product Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="List of category names used by products",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="string",
     *                 example="Hair Care"
     *             )
     *         )
     *     )
     * )
     */
    public function productCategories()
    {
        // Get all categories that are associated with at least one product
        // Only select id and name fields
        $categoriesWithProducts = Category::select(['id', 'name'])
            ->whereHas('products')
            ->get();
        
        // Extract just the names as a simple array
        $categoryNames = $categoriesWithProducts->pluck('name');
        
        return response()->json($categoryNames);
    }
}
