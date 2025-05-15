<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Product Categories",
 *     description="API Endpoints for managing product categories relationships"
 * )
 */
class ProductCategoryApiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/products/{productId}/categories",
     *     summary="Get all categories for a product",
     *     tags={"Product Categories"},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         description="ID of product",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of product categories",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Category Name")
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
     * 
     * API Endpoint: GET /api/products/{productId}/categories
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
     * @OA\Put(
     *     path="/api/products/{productId}/categories/sync",
     *     summary="Sync categories for a product (replace all existing with the provided set)",
     *     tags={"Product Categories"},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         description="ID of product",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Add and remove categories. Send an array of category ids to add and/or change the current categories, send an empty array to remove all.",
     *         @OA\JsonContent(
     *             required={"categories"},
     *             @OA\Property(
     *                 property="categories",
     *                 type="array",
     *                 description="Array of category IDs. Send an empty array [] to remove all categories.",
     *                 @OA\Items(type="integer"),
     *                 example={1, 2, 3}
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
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Product Name"),
     *                 @OA\Property(property="description", type="string", example="Product description"),
     *                 @OA\Property(property="price", type="number", format="float", example=99.99),
     *                 @OA\Property(property="stock", type="integer", example=10),
     *                 @OA\Property(
     *                     property="categories",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Category Name")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found or category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product not found"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={"categories.0": {"The selected categories.0 is invalid."}}
     *             )
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
     *                 example={"categories": {"The categories field is required"}}
     *             )
     *         )
     *     )
     * )
     * 
     * API Endpoint: PUT /api/products/{productId}/categories/sync
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
                ], 404);
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
     * @OA\Get(
     *     path="/api/products-with-categories",
     *     summary="Get all products with their associated categories",
     *     tags={"Product Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="List of products with their categories",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Product Name"),
     *                 @OA\Property(
     *                     property="categories",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Category Name")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     * 
     * API Endpoint: GET /api/products-with-categories
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
     * @OA\Get(
     *     path="/api/product-categories",
     *     summary="Get all categories that are associated with any products",
     *     tags={"Product Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="List of category names used by products",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="string", example="Category Name")
     *         )
     *     )
     * )
     * 
     * API Endpoint: GET /api/product-categories
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
