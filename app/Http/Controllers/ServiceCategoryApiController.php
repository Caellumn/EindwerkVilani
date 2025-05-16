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
     *     path="/api/services/{serviceId}/categories",
     *     tags={"Service Categories"},
     *     summary="Get all categories for a service",
     *     @OA\Parameter(
     *         name="serviceId",
     *         in="path",
     *         description="ID of service",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of service categories",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="name", type="string", example="Category Name")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service not found")
     *         )
     *     )
     * )
     * 
     * API Endpoint: GET /api/services/{serviceId}/categories
     */
    public function index(Request $request, $serviceId)
    {
        // Find the service by ID
        $service = Service::find($serviceId);
        
        // Return 404 if service not found
        if (!$service) {
            return response()->json([
                'message' => 'Service not found'
            ], 404);
        }
        
        // Return categories
        return $service->categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name
            ];
        });
    }

    /**
     * @OA\Get(
     *     path="/api/services-with-categories",
     *     summary="Get all services with their associated categories",
     *     tags={"Service Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="List of services with their categories",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="name", type="string", example="Service Name"),
     *                 @OA\Property(
     *                     property="categories",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
     *                         @OA\Property(property="name", type="string", example="Category Name")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     * 
     * API Endpoint: GET /api/services-with-categories
     */
    public function servicesWithCategories()
    {
        // Get services with their categories but only select id and name fields
        $services = Service::with(['categories' => function($query) {
            $query->select(['categories.id', 'categories.name']);
        }])->get(['id', 'name']);
        
        // Transform the data to include only what's needed
        $formattedServices = $services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'categories' => $service->categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name
                    ];
                })
            ];
        });
        
        return response()->json($formattedServices);
    }

    /**
     * @OA\Get(
     *     path="/api/service-categories",
     *     summary="Get all categories that are associated with any services",
     *     tags={"Service Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="List of category names used by services",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="string", example="Category Name")
     *         )
     *     )
     * )
     * 
     * API Endpoint: GET /api/service-categories
     */
    public function serviceCategories()
    {
        // Get all categories that are associated with at least one service
        // Only select id and name fields
        $categoriesWithServices = Category::select(['id', 'name'])
            ->whereHas('services')
            ->get();
        
        // Extract just the names as a simple array
        $categoryNames = $categoriesWithServices->pluck('name');
        
        return response()->json($categoryNames);
    }

    /**
     * Sync categories for a service
     * 
     * @OA\Put(
     *     path="/api/services/{serviceId}/categories/sync",
     *     tags={"Service Categories"},
     *     summary="Sync categories for a service",
     *     @OA\Parameter(
     *         name="serviceId",
     *         in="path",
     *         description="ID of service",
     *         required=true,
     *         @OA\Schema(type="string")
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
     *                 @OA\Items(type="string"),
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
     *                 property="service",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="name", type="string", example="Service Name"),
     *                 @OA\Property(property="description", type="string", example="Service description"),
     *                 @OA\Property(property="hairlength", type="string", example="medium"),
     *                 @OA\Property(property="price", type="number", format="float", example=99.99),
     *                 @OA\Property(property="active", type="integer", example=1),
     *                 @OA\Property(
     *                     property="categories",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
     *                         @OA\Property(property="name", type="string", example="Category Name")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service not found or category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service not found"),
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
     * API Endpoint: PUT /api/services/{serviceId}/categories/sync
     */
    public function sync(Request $request, $serviceId)
    {
        // Find the service by ID
        $service = Service::find($serviceId);
        
        // Return 404 if service not found
        if (!$service) {
            return response()->json([
                'message' => 'Service not found'
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
        $service->categories()->sync($categories);

        // Load the categories with only id and name fields
        $service->load(['categories' => function($query) {
            $query->select('categories.id', 'categories.name');
        }]);
        
        // Format the response to only include necessary fields
        $formattedService = [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'hairlength' => $service->hairlength,
            'price' => $service->price,
            'active' => $service->active,
            'categories' => $service->categories->map(function($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name
                ];
            })
        ];

        // Return a success response with the updated service and its categories
        return response()->json([
            'message' => 'Categories synced successfully',
            'service' => $formattedService
        ]);
    }
}
