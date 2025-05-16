<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @OA\Get(
     *     path="/products",
     *     tags={"Products"},
     *     summary="Get all products",
     *     description="Returns list of all products",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Product"))
     *     )
     * )
     */
    public function index()
    {
        //
        return Product::all();
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @OA\Post(
     *     path="/products",
     *     tags={"Products"},
     *     summary="Create a new product",
     *     description="Creates a new product and returns the product details",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Required fields: name, description, price, stock. Optional field: image",
     *         @OA\JsonContent(
     *             required={"name", "description", "price", "stock"},
     *             @OA\Property(property="name", type="string", example="Shampoo", description="Required. Name of the product"),
     *             @OA\Property(property="description", type="string", example="Something to wash your hair", description="Required. Description of the product"),
     *             @OA\Property(property="price", type="number", format="float", example=9.99, description="Required. Price of the product"),
     *             @OA\Property(property="stock", type="integer", example=50, description="Required. Number of items in stock"),
     *             @OA\Property(property="image", type="string", example="shampoo.jpg", description="Optional. Image filename for the product")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Product already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product already exists")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request)
    {
     $name = $request->name;
     $description = $request->description;
     $price = $request->price;
     $stock = $request->stock;
     $image = $request->image;

     //check if the product name is already in the database
     $product = Product::where('name', $name)->first();
     if ($product) {                                                     /*error code*/  
        return response()->json(['message' => 'Product already exists'], 400);
     }
     $product = Product::create([
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'stock' => $stock,
        'image' => $image
     ]);
     return $product;
    }

    /**
     * Display the specified resource.
     * 
     * @OA\Get(
     *     path="/products/{id}",
     *     tags={"Products"},
     *     summary="Get product by ID",
     *     description="Returns a single product",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of product to return",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */
    public function show(Product $product)
    {
        //show me a single product in json format
        return $product;
    }

    /**
     * Update the specified resource in storage.
     * 
     * @OA\Put(
     *     path="/products/{id}",
     *     tags={"Products"},
     *     summary="Update an existing product",
     *     description="Updates a product and returns the updated product details",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of product to update",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Shampoo"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="price", type="number", example=19.99),
     *             @OA\Property(property="stock", type="integer", example=100),
     *             @OA\Property(property="image", type="string", example="updated-shampoo.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */
    public function update(Request $request, Product $product)
    {
        // $product = Product::find($product);
        
        $product->name = $request->name;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->stock = $request->stock;
        $product->image = $request->image;
        $product->save();
        return $product;
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @OA\Delete(
     *     path="/products/{id}",
     *     tags={"Products"},
     *     summary="Soft delete a product",
     *     description="Marks a product as inactive (soft delete) instead of removing it from the database",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of product to delete",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */
    public function destroy(Product $product)
    {
        //remove a product from the database by name
        $product->active = 0;
        $product->save();
        return response()->json(['message' => 'Product deleted successfully'], 200);
    }
}
