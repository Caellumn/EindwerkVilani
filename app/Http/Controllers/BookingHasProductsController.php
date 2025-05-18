<?php

namespace App\Http\Controllers;

use App\Models\BookingProduct;
use App\Models\Booking;
use App\Models\Product;
use Illuminate\Http\Request;

class BookingHasProductsController extends Controller
{
    /**
     * Display a listing of products for a specific booking.
     * 
     * @OA\Get(
     *     path="/api/bookings/{bookingId}/products",
     *     tags={"Bookings", "Products"},
     *     summary="Get products for a specific booking",
     *     description="Returns all products associated with the specified booking",
     *     @OA\Parameter(
     *         name="bookingId",
     *         in="path",
     *         description="ID of the booking",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of products",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Product Name")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function index(Request $request, $bookingId)
    {
         // Find the product by ID
         $booking = Booking::find($bookingId);
        
         // Return 404 if booking not found
         if (!$booking) {
             return response()->json([
                 'message' => 'Booking not found'
             ], 404);
         }
         
         // Return products with only id and name
         return $booking->products->map(function ($product) {
             return [
                 'id' => $product->id,
                 'name' => $product->name
             ];
         });
    }

    /**
     * Display all bookings with their associated products.
     * 
     * @OA\Get(
     *     path="/api/bookings-with-products",
     *     tags={"Bookings", "Products"},
     *     summary="Get all bookings with their products",
     *     description="Returns all bookings with their associated products",
     *     @OA\Response(
     *         response=200,
     *         description="List of bookings with their products",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Booking Name"),
     *                 @OA\Property(
     *                     property="products",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Product Name")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function bookingsWithProducts()
    {
        // Get bookings with their products but only select id and name fields
        $bookings = Booking::with(['products' => function($query) {
            $query->select(['products.id', 'products.name']);
        }])->get(['id', 'name']);
        
        // Transform the data to include only what's needed
        $formattedBookings = $bookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'name' => $booking->name,
                'products' => $booking->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name
                    ];
                })
            ];
        });
        
        return response()->json($formattedBookings);
    }

    /**
     * Update the products associated with a booking.
     * 
     * @OA\Put(
     *     path="/api/bookings/{bookingId}/products/sync",
     *     tags={"Bookings", "Products"},
     *     summary="Sync products for a booking",
     *     description="Update the products associated with a specific booking",
     *     @OA\Parameter(
     *         name="bookingId",
     *         in="path",
     *         description="ID of the booking",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/BookingProductRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Products synced successfully",
     *         @OA\JsonContent(ref="#/components/schemas/BookingProductResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or invalid product IDs",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function syncProducts(Request $request, $bookingId)
    {
         // Find the booking by ID
         $booking = Booking::find($bookingId);
        
         // Return 404 if booking not found
         if (!$booking) {
             return response()->json([
                 'message' => 'Booking not found'
             ], 404);
         }
         
         // Check if products field exists in the request
         if (!$request->has('products')) {
             return response()->json([
                 'message' => 'The products field is required',
                 'errors' => [
                     'products' => ['The products field is required']
                 ]
             ], 422);
         }
         
         // Validate that the request contains an array field
         $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
             'products' => 'present|array',
         ]);
         
         if ($validator->fails()) {
             return response()->json([
                 'message' => 'Validation failed',
                 'errors' => $validator->errors()
             ], 422);
         }
 
         // Only validate product IDs if array is not empty
         if (count($request->products) > 0) {
             $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                 'products.*' => 'exists:products,id'
             ]);
             
             if ($validator->fails()) {
                 return response()->json([
                     'message' => 'One or more product IDs are invalid',
                     'errors' => $validator->errors()
                 ], 422);
             }
             
             // Convert the array of product IDs into a collection
             $products = collect($request->products)->mapWithKeys(function ($productId) {
                 return [$productId => []];
             });
         } else {
             // Empty array means we want to remove all products
             $products = [];
         }
 
         // The sync method:
         // 1. Detaches all products not in the provided array
         // 2. Attaches any new products from the array
         // 3. Updates pivot data for products that remain
         $booking->products()->sync($products);
 
         // Load the products with only id and name fields
         $booking->load(['products' => function($query) {
             $query->select('products.id', 'products.name');
         }]);
         
         // Format the response to only include necessary fields
         $formattedBooking = [
             'id' => $booking->id,
             'products' => $booking->products->map(function($product) {
                 return [
                     'id' => $product->id,
                     'name' => $product->name
                 ];
             })
         ];
 
         // Return a success response with the updated booking and its products
         return response()->json([
             'message' => 'Products synced successfully',
             'booking' => $formattedBooking
         ]);
    }

    /**
     * Get all products that are associated with at least one booking.
     * 
     * @OA\Get(
     *     path="/api/booking-products",
     *     tags={"Bookings", "Products"},
     *     summary="Get all products used in bookings",
     *     description="Returns all products that are associated with at least one booking",
     *     @OA\Response(
     *         response=200,
     *         description="List of product names",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="string", example="Product Name")
     *         )
     *     )
     * )
     */
    public function bookingProducts()
    {
        // Get all products that are associated with at least one booking
        // Only select id and name fields
        $productsWithBookings = Product::select(['id', 'name'])
            ->whereHas('bookings')
            ->get();
        
        // Extract just the names as a simple array
        $productNames = $productsWithBookings->pluck('name');
        
        return response()->json($productNames);
    }
}
