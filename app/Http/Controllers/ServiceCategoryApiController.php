<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Category;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class ServiceCategoryApiController extends Controller
{
    /**
     * Get all categories for a service
     * 
     * @OA\Get(
     *     path="/services/{service}/categories",
     *     tags={"Service Categories"},
     *     summary="Get all categories for a service",
     *     @OA\Parameter(
     *         name="service",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service not found"
     *     )
     * )
     */
    public function index(Service $service)
    {
        return $service->categories;
    }

    /**
     * Attach categories to a service
     * 
     * @OA\Post(
     *     path="/services/{service}/categories",
     *     tags={"Service Categories"},
     *     summary="Attach categories to a service",
     *     @OA\Parameter(
     *         name="service",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="categories", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categories attached successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function attach(Request $request, Service $service)
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


        $service->categories()->attach($categories);

        

        return response()->json([
            'message' => 'Categories attached successfully',
            'service' => $service->load('categories')
        ]);
    }

    /**
     * Detach categories from a service
     * 
     * @OA\Delete(
     *     path="/services/{service}/categories",
     *     tags={"Service Categories"},
     *     summary="Detach categories from a service",
     *     @OA\Parameter(
     *         name="service",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="categories", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categories detached successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service not found"
     *     )
     * )
     */
    public function detach(Request $request, Service $service)
    {
        if ($request->has('categories')) {
            $service->categories()->detach($request->categories);
            $message = 'Specified categories detached successfully';
        } else {
            $service->categories()->detach();
            $message = 'All categories detached successfully';
        }

        //if no cateogry was found send a 404 error
        if (!$service->categories()->exists()) {
            return response()->json([
                'message' => 'No categories were found'
            ], 404);
        }

        return response()->json([
            'message' => $message,
            'service' => $service->load('categories')
        ]);
    }

    /**
     * Sync categories for a service
     * 
     * @OA\Put(
     *     path="/services/{service}/categories",
     *     tags={"Service Categories"},
     *     summary="Sync categories for a service",
     *     @OA\Parameter(
     *         name="service",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="categories", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categories synced successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function sync(Request $request, Service $service)
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
        $service->categories()->sync($categories);

        // Return a success response with the updated service and its categories
        return response()->json([
            'message' => 'Categories synced successfully',
            'service' => $service->load('categories')
        ]);
    }

    /**
     * Update category status for a service
     * 
     * @OA\Patch(
     *     path="/services/{service}/categories/{category}",
     *     tags={"Service Categories"},
     *     summary="Update status for a service-category relationship",
     *     @OA\Parameter(
     *         name="service",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="active", type="integer", example=0)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Relationship not found"
     *     )
     * )
     */
    public function updateStatus(Request $request, Service $service, Category $category)
    {
        $request->validate([
            'active' => 'required|integer|in:0,1'
        ]);

        // Check if the relationship exists
        if (!$service->categories()->where('category_id', $category->id)->exists()) {
            return response()->json([
                'message' => 'This service is not associated with the specified category'
            ], 404);
        }

        $service->categories()->updateExistingPivot($category->id, [
            'active' => $request->active
        ]);

        return response()->json([
            'message' => 'Status updated successfully',
            'service' => $service->load('categories')
        ]);
    }

    /**
     * Toggle service-category relationship
     * 
     * @OA\Post(
     *     path="/services/{service}/categories/toggle",
     *     tags={"Service Categories"},
     *     summary="Toggle categories for a service",
     *     @OA\Parameter(
     *         name="service",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="categories", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categories toggled successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service not found"
     *     )
     * )
     */
    public function toggle(Request $request, Service $service)
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id'
        ]);

        $categories = collect($request->categories)->mapWithKeys(function ($categoryId) {
            return [$categoryId => ['active' => 1]];
        });

        $service->categories()->toggle($categories);

        return response()->json([
            'message' => 'Categories toggled successfully',
            'service' => $service->load('categories')
        ]);
    }
}
