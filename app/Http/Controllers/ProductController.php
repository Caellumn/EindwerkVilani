<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use OpenApi\Annotations as OA;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @OA\Get(
     *     path="/api/products",
     *     summary="Get all active products",
     *     description="Returns a paginated list of active products (max 20 per page)",
     *     operationId="getProducts",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                     @OA\Property(property="name", type="string", example="Shampoo"),
     *                     @OA\Property(property="description", type="string", example="Professional hair shampoo"),
     *                     @OA\Property(property="price", type="number", format="float", example=19.99),
     *                     @OA\Property(property="stock", type="integer", example=50),
     *                     @OA\Property(property="image", type="string", nullable=true, example="https://cloudinary.com/image.jpg"),
     *                     @OA\Property(property="active", type="integer", example=1)
     *                 )
     *             ),
     *             @OA\Property(property="links", type="object",
     *                 @OA\Property(property="first", type="string", example="http://example.com/api/products?page=1"),
     *                 @OA\Property(property="last", type="string", example="http://example.com/api/products?page=10"),
     *                 @OA\Property(property="prev", type="string", nullable=true, example="http://example.com/api/products?page=1"),
     *                 @OA\Property(property="next", type="string", nullable=true, example="http://example.com/api/products?page=3")
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=2),
     *                 @OA\Property(property="from", type="integer", example=21),
     *                 @OA\Property(property="last_page", type="integer", example=10),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="to", type="integer", example=40),
     *                 @OA\Property(property="total", type="integer", example=200)
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        return Product::where('active', 1)->paginate(20);
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @OA\Post(
     *     path="/api/products",
     *     summary="Create a new product",
     *     description="Creates a new product and returns the product details",
     *     operationId="createProduct",
     *     tags={"Products"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Product creation data",
     *         @OA\JsonContent(
     *             required={"name", "description", "price", "stock"},
     *             @OA\Property(property="name", type="string", example="Shampoo"),
     *             @OA\Property(property="description", type="string", example="Professional hair shampoo"),
     *             @OA\Property(property="price", type="number", format="float", example=19.99),
     *             @OA\Property(property="stock", type="integer", example=50),
     *             @OA\Property(property="image", type="string", nullable=true, example="https://cloudinary.com/image.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="name", type="string", example="Shampoo"),
     *             @OA\Property(property="description", type="string", example="Professional hair shampoo"),
     *             @OA\Property(property="price", type="number", format="float", example=19.99),
     *             @OA\Property(property="stock", type="integer", example=50),
     *             @OA\Property(property="image", type="string", nullable=true, example="https://cloudinary.com/image.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Product already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product already exists")
     *         )
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
     *     path="/api/products/{id}",
     *     summary="Get product by ID",
     *     description="Returns a single product",
     *     operationId="getProductById",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of product to return",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="name", type="string", example="Shampoo"),
     *             @OA\Property(property="description", type="string", example="Professional hair shampoo"),
     *             @OA\Property(property="price", type="number", format="float", example=19.99),
     *             @OA\Property(property="stock", type="integer", example=50),
     *             @OA\Property(property="image", type="string", nullable=true, example="https://cloudinary.com/image.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */
    public function show(Product $product)
    {
        return $product;
    }

    /**
     * Update the specified resource in storage.
     * 
     * @OA\Put(
     *     path="/api/products/{id}",
     *     summary="Update an existing product",
     *     description="Updates a product and returns the updated product details",
     *     operationId="updateProduct",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of product to update",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Shampoo"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="price", type="number", format="float", example=21.99),
     *             @OA\Property(property="stock", type="integer", example=75),
     *             @OA\Property(property="image", type="string", nullable=true, example="https://cloudinary.com/new-image.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="name", type="string", example="Updated Shampoo"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="price", type="number", format="float", example=21.99),
     *             @OA\Property(property="stock", type="integer", example=75),
     *             @OA\Property(property="image", type="string", nullable=true, example="https://cloudinary.com/new-image.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */
    public function update(Request $request, Product $product)
    {
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
     *     path="/api/products/{id}",
     *     summary="Soft delete a product",
     *     description="Soft deletes a product by setting active to 0",
     *     operationId="deleteProduct",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of product to delete",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
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
        $product->active = 0;
        $product->save();
        return response()->json(['message' => 'Product deleted successfully'], 200);
    }

    /**
     * Show upload form (web route).
     * 
     * @OA\Get(
     *     path="/products/upload",
     *     summary="Show product image upload form",
     *     description="Returns the upload form view (web interface)",
     *     operationId="showUploadForm",
     *     tags={"Products"},
     *     @OA\Response(
     *         response=200,
     *         description="Upload form view",
     *         @OA\MediaType(
     *             mediaType="text/html"
     *         )
     *     )
     * )
     */
    public function showUploadForm()
    {
        return view('upload');
    }

    /**
     * Store uploaded file to Cloudinary and update product.
     * 
     * @OA\Post(
     *     path="/products/upload",
     *     summary="Upload product image to Cloudinary",
     *     description="Uploads an image file to Cloudinary and updates the product's image URL",
     *     operationId="storeUploads",
     *     tags={"Products"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file", "product_id"},
     *                 @OA\Property(property="file", type="string", format="binary", description="Image file (jpeg, png, jpg, gif, max 2MB)"),
     *                 @OA\Property(property="product_id", type="string", format="uuid", description="ID of existing product to update")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Redirect back with success message"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "file": {"The file must be an image."},
     *                     "product_id": {"The selected product_id is invalid."}
     *                 }
     *             )
     *         )
     *     )
     * )
     */
    public function storeUploads(Request $request)
    {
        // Validate the request
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'product_id' => 'required|exists:products,id' // if updating existing product
        ]);
    
        try {
            // Upload to Cloudinary
            $response = Cloudinary::upload($request->file('file')->getRealPath())->getSecurePath();
            
            // Update the product in database
            $product = Product::findOrFail($request->product_id);
            $product->image = $response;
            $product->save();
            
            return back()->with('success', 'File uploaded and product updated successfully');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }
}
