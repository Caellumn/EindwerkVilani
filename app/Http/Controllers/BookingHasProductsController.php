<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Product;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class BookingHasProductsController extends Controller
{
    /**
     * Display the products for a specific booking.
     * 
     * @OA\Get(
     *     path="/api/bookings/{bookingId}/products",
     *     summary="Get products for a specific booking",
     *     description="Returns a list of products associated with a specific booking",
     *     operationId="getProductsByBooking",
     *     tags={"Booking Products"},
     *     @OA\Parameter(
     *         name="bookingId",
     *         in="path",
     *         description="ID of booking to get products for",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="name", type="string", example="Hair Shampoo")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking not found")
     *         )
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
     *     summary="Get all bookings with their associated products",
     *     description="Returns a list of all bookings with their associated products (id and name only)",
     *     operationId="getBookingsWithProducts",
     *     tags={"Booking Products"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="name", type="string", example="John Doe Appointment"),
     *                 @OA\Property(
     *                     property="products",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                         @OA\Property(property="name", type="string", example="Hair Shampoo")
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
     *     summary="Sync products for a booking",
     *     description="Replace all current products associated with a booking with the provided set. Send empty array to remove all products.",
     *     operationId="syncBookingProducts",
     *     tags={"Booking Products"},
     *     @OA\Parameter(
     *         name="bookingId",
     *         in="path",
     *         description="ID of booking to sync products for",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Array of product IDs to associate with the booking",
     *         @OA\JsonContent(
     *             required={"products"},
     *             @OA\Property(
     *                 property="products",
     *                 type="array",
     *                 description="Array of product IDs. Send empty array [] to remove all products.",
     *                 @OA\Items(type="string", format="uuid"),
     *                 example={"123e4567-e89b-12d3-a456-426614174000", "123e4567-e89b-12d3-a456-426614174001"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Products synced successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Products synced successfully"),
     *             @OA\Property(
     *                 property="booking",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(
     *                     property="products",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                         @OA\Property(property="name", type="string", example="Hair Shampoo")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The products field is required"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "products": {"The products field is required"},
     *                     "products.0": {"The selected products.0 is invalid."}
     *                 }
     *             )
     *         )
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
     *     summary="Get all products used in bookings",
     *     description="Returns a list of product names that are associated with at least one booking",
     *     operationId="getProductsUsedInBookings",
     *     tags={"Booking Products"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="string",
     *                 example="Hair Shampoo"
     *             )
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
