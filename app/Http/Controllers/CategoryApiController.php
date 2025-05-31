<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class CategoryApiController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @OA\Get(
     *     path="/api/categories",
     *     summary="Get all categories",
     *     description="Returns a list of all categories",
     *     operationId="getCategories",
     *     tags={"Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="name", type="string", example="Hair Care"),
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
     *     description="Creates a new category with validation",
     *     operationId="createCategory",
     *     tags={"Categories"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Category creation data",
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Hair Care", description="Category name (must be unique)"),
     *             @OA\Property(property="active", type="boolean", example=true, description="Active status (optional, defaults to true)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="name", type="string", example="Hair Care"),
     *             @OA\Property(property="active", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "name": {"The name field is required.", "The name has already been taken."}
     *                 }
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
     *     path="/api/categories/{id}",
     *     summary="Get category by ID",
     *     description="Returns a single category",
     *     operationId="getCategoryById",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of category to return",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="name", type="string", example="Hair Care"),
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
     *     path="/api/categories/{id}",
     *     summary="Update an existing category",
     *     description="Updates a category with validation",
     *     operationId="updateCategory",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of category to update",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Updated Hair Care"),
     *             @OA\Property(property="active", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="name", type="string", example="Updated Hair Care"),
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
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "name": {"The name has already been taken."}
     *                 }
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
     *     path="/api/categories/{id}",
     *     summary="Soft delete a category",
     *     description="Soft deletes a category by setting active to 0",
     *     operationId="deleteCategory",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of category to delete",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Category deleted successfully (No Content)"
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
