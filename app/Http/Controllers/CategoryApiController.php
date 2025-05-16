<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Categories",
 *     description="API Endpoints for managing categories"
 * )
 */
class CategoryApiController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @OA\Get(
     *     path="/api/categories",
     *     summary="Get all categories",
     *     tags={"Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="List of categories",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="name", type="string", example="Category Name"),
     *                 @OA\Property(property="active", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No categories found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No categories were found")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $categories = Category::all();
        
        // If no categories are found, return 404 with a message
        if ($categories->isEmpty()) {
            return response()->json([
                'message' => 'No categories were found'
            ], 404);
        }

        //remove updated_at and created_at from the response
        $categories->makeHidden(['updated_at', 'created_at']);  
        
        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @OA\Post(
     *     path="/api/categories",
     *     summary="Create a new category",
     *     description="Creates a new category. Only the name field is mandatory, active is optional.",
     *     tags={"Categories"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Category data",
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(
     *                 property="name", 
     *                 type="string", 
     *                 example="New Category",
     *                 description="Category name (Required)"
     *             ),
     *             @OA\Property(
     *                 property="active", 
     *                 type="integer", 
     *                 example=1, 
     *                 description="Category status (Optional, default: 1)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="New Category"),
     *             @OA\Property(property="active", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid or missing fields",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The name field is required"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={"name": {"The name field is required"}}
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        // Validate request
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories',
            'active' => 'sometimes|boolean'
        ]);
        
        // Return 422 error if validation fails
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Create category with validated data
        $category = Category::create($validator->validated());

        //remove updated_at and created_at from the response
        $category->makeHidden(['updated_at', 'created_at']);
        
        // Return created category with 201 status
        return response()->json($category, 201);
    }

    /**
     * Display the specified resource.
     * 
     * @OA\Get(
     *     path="/api/categories/{categoryId}",
     *     summary="Get a specific category by ID",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="categoryId",
     *         in="path",
     *         description="ID of category to return",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="name", type="string", example="Category Name"),
     *             @OA\Property(property="active", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category not found")
     *         )
     *     )
     * )
     */
    public function show(Request $request, $categoryId)
    {
        // Find the category by ID
        $category = Category::find($categoryId);
        
        // Return 404 if category not found
        if (!$category) {
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }
        
        //remove updated_at and created_at from the response
        $category->makeHidden(['updated_at', 'created_at']);
        
        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     * 
     * @OA\Put(
     *     path="/api/categories/{categoryId}",
     *     summary="Update an existing category",
     *     description="Updates an existing category. name is required.",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="categoryId",
     *         in="path",
     *         description="ID of category to update",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Category data. name is required.",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Category Name"),
     *             @OA\Property(property="active", type="integer", example=1, description="Optional field")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="name", type="string", example="Updated Category Name"),
     *             @OA\Property(property="active", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid fields",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={"name": {"The name field must be unique"}}
     *             )
     *         )
     *     )
     * )
     */
    public function update(Request $request, $categoryId)
    {
        // Find the category by ID
        $category = Category::find($categoryId);
        
        // Return 404 if category not found
        if (!$category) {
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }
        
        // Validate request
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'active' => 'sometimes|integer'
        ]);
        
        // Return 422 error if validation fails
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Update category with validated data
        $category->update($validator->validated());

        //remove updated_at and created_at from the response
        $category->makeHidden(['updated_at', 'created_at']);
        
        // Return updated category
        return response()->json($category);
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @OA\Delete(
     *     path="/api/categories/{categoryId}",
     *     summary="Deactivate a category (soft delete)",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="categoryId",
     *         in="path",
     *         description="ID of category to deactivate",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Category deactivated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category not found")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, $categoryId)
    {
        // Find the category by ID
        $category = Category::find($categoryId);
        
        // Return 404 if category not found
        if (!$category) {
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }
        
        // Soft delete by setting active to 0
        $category->active = 0;
        $category->save();

        //remove updated_at and created_at from the response
        $category->makeHidden(['updated_at', 'created_at']);
            
        // Return 204 No Content
        return response()->json(null, 204);
    }
}
