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
     *     path="/api/products/{product}/categories",
     *     summary="Get all categories for a product",
     *     tags={"Product Categories"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="ID of product",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of product categories",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Category"))
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     * 
     * API Endpoint: GET /api/products/{product}/categories
     */
    public function index(Product $product)
    {
        //remove the created at and updated at from the response
        return $product->categories->map(function ($category) {
            $category->makeHidden(['created_at', 'updated_at']);
            return $category;
        });
    }

    /**
     * @OA\Post(
     *     path="/api/products/{product}/categories",
     *     summary="Attach categories to a product",
     *     tags={"Product Categories"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="ID of product",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"categories"},
     *             @OA\Property(
     *                 property="categories",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={1, 2, 3}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categories attached successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Categories attached successfully"),
     *             @OA\Property(
     *                 property="product",
     *                 type="object",
     *                 ref="#/components/schemas/Product"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product or categories not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     * 
     * API Endpoint: POST /api/products/{product}/categories
     */
    public function attach(Request $request, Product $product)
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id'
        ]);


        $categories = collect($request->categories)->mapWithKeys(function ($categoryId) {
            return [$categoryId => ['active' => 1]];
        });

        if (!Category::whereIn('id', $request->categories)->exists()) {
            return response()->json([
                'message' => 'Categories not found'
            ], 404);
        }

        //if no body was given send a 422 error
        if (!$request->has('categories')) {
            return response()->json([
                'message' => 'No categories were given'
            ], 422);
        }


        $product->categories()->attach($categories);

        

        return response()->json([
            'message' => 'Categories attached successfully',
            'product' => $product->load('categories')
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/products/{product}/categories",
     *     summary="Detach categories from a product",
     *     tags={"Product Categories"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="ID of product",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="categories",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={1, 2},
     *                 description="Optional. If not provided, all categories will be detached"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categories detached successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Categories detached successfully"),
     *             @OA\Property(
     *                 property="product",
     *                 type="object",
     *                 ref="#/components/schemas/Product"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found or no categories found"
     *     )
     * )
     * 
     * API Endpoint: DELETE /api/products/{product}/categories
     */
    public function detach(Request $request, Product $product)
    {
        if ($request->has('categories')) {
            $product->categories()->detach($request->categories);
            $message = 'Specified categories detached successfully';
        } else {
            $product->categories()->detach();
            $message = 'All categories detached successfully';
        }

        //if no cateogry was found send a 404 error
        if (!$product->categories()->exists()) {
            return response()->json([
                'message' => 'No categories were found'
            ], 404);
        }

        return response()->json([
            'message' => $message,
            'product' => $product->load('categories')
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/products/{product}/categories/sync",
     *     summary="Sync categories for a product (replace all existing with the provided set)",
     *     tags={"Product Categories"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="ID of product",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"categories"},
     *             @OA\Property(
     *                 property="categories",
     *                 type="array",
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
     *                 ref="#/components/schemas/Product"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     * 
     * API Endpoint: PUT /api/products/{product}/categories/sync
     */
    public function sync(Request $request, Product $product)
    {
        // Validate that the request contains an array of valid category IDs
        $request->validate([
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id'
        ]);

        // Convert the array of category IDs into a collection with pivot data
        // For each category ID, we set 'active' => 1 in the pivot table
        $categories = collect($request->categories)->mapWithKeys(function ($categoryId) {
            return [$categoryId => ['active' => 1]];
        });

        // The sync method:
        // 1. Detaches all categories not in the provided array
        // 2. Attaches any new categories from the array
        // 3. Updates pivot data for categories that remain
        $product->categories()->sync($categories);

        // Return a success response with the updated service and its categories
        return response()->json([
            'message' => 'Categories synced successfully',
            'product' => $product->load('categories')
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/api/products/{product}/categories/{category}",
     *     summary="Update the active status of a specific product-category relationship",
     *     tags={"Product Categories"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="ID of product",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         description="ID of category",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"active"},
     *             @OA\Property(
     *                 property="active",
     *                 type="integer",
     *                 enum={0, 1},
     *                 example=1,
     *                 description="Status: 1 for active, 0 for inactive"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Status updated successfully"),
     *             @OA\Property(
     *                 property="product",
     *                 type="object",
     *                 ref="#/components/schemas/Product"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found or category not associated with product"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     * 
     * API Endpoint: PATCH /api/products/{product}/categories/{category}
     */
    public function updateStatus(Request $request, Product $product, Category $category)
    {
        $request->validate([
            'active' => 'required|integer|in:0,1'
        ]);

        // Check if the relationship exists
        if (!$product->categories()->where('category_id', $category->id)->exists()) {
            return response()->json([
                'message' => 'This product is not associated with the specified category'
            ], 404);
        }

        $product->categories()->updateExistingPivot($category->id, [
            'active' => $request->active
        ]);

        return response()->json([
            'message' => 'Status updated successfully',
            'product' => $product->load('categories')
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/products/{product}/categories/toggle",
     *     summary="Toggle categories for a product (attach if not exists, detach if exists)",
     *     tags={"Product Categories"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="ID of product",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"categories"},
     *             @OA\Property(
     *                 property="categories",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={1, 2, 3}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categories toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Categories toggled successfully"),
     *             @OA\Property(
     *                 property="product",
     *                 type="object",
     *                 ref="#/components/schemas/Product"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     * 
     * API Endpoint: POST /api/products/{product}/categories/toggle
     */
    public function toggle(Request $request, Product $product)
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id'
        ]);

        $categories = collect($request->categories)->mapWithKeys(function ($categoryId) {
            return [$categoryId => ['active' => 1]];
        });

        $product->categories()->toggle($categories);

        return response()->json([
            'message' => 'Categories toggled successfully',
            'product' => $product->load('categories')
        ]);
    }
}
