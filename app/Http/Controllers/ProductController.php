<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return Product::all();
    }

    /**
     * Store a newly created resource in storage.
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
     */
    public function show(Product $product)
    {
        //show me a single product in json format
        return Product::find($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }
}
