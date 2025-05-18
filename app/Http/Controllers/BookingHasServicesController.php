<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class BookingHasServicesController extends Controller
{
    /**
     * Display a listing of services for a specific booking.
     *
     * @OA\Get(
     *     path="/api/bookings/{bookingId}/services",
     *     tags={"Bookings", "Services"},
     *     summary="Get services for a specific booking",
     *     description="Returns all services associated with the specified booking",
     *     @OA\Parameter(
     *         name="bookingId",
     *         in="path",
     *         description="ID of the booking",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of services",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Service Name")
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
         
         // Return services with only id and name
         return $booking->services->map(function ($service) {
             return [
                 'id' => $service->id,
                 'name' => $service->name
             ];
         });
    }

    /**
     * Display all bookings with their associated services.
     *
     * @OA\Get(
     *     path="/api/bookings-with-services",
     *     tags={"Bookings", "Services"},
     *     summary="Get all bookings with their services",
     *     description="Returns all bookings with their associated services",
     *     @OA\Response(
     *         response=200,
     *         description="List of bookings with their services",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Booking Name"),
     *                 @OA\Property(
     *                     property="services",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Service Name")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function bookingsWithServices()
    {
        // Get products with their categories but only select id and name fields
        $bookings = Booking::with(['services' => function($query) {
            $query->select(['services.id', 'services.name']);
        }])->get(['id', 'name']);
        
        // Transform the data to include only what's needed
        $formattedBookings = $bookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'name' => $booking->name,
                'services' => $booking->services->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name
                    ];
                })
            ];
        });
        
        return response()->json($formattedBookings);
    }

    /**
     * Update the services associated with a booking.
     *
     * @OA\Put(
     *     path="/api/bookings/{bookingId}/services/sync",
     *     tags={"Bookings", "Services"},
     *     summary="Sync services for a booking",
     *     description="Update the services associated with a specific booking",
     *     @OA\Parameter(
     *         name="bookingId",
     *         in="path",
     *         description="ID of the booking",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/BookingServiceRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Services synced successfully",
     *         @OA\JsonContent(ref="#/components/schemas/BookingServiceResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or invalid service IDs",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function syncServices(Request $request, $bookingId)
    {
         // Find the booking by ID
         $booking = Booking::find($bookingId);
        
         // Return 404 if booking not found
         if (!$booking) {
             return response()->json([
                 'message' => 'Booking not found'
             ], 404);
         }
         
         // Check if categories field exists in the request
         if (!$request->has('services')) {
             return response()->json([
                 'message' => 'The services field is required',
                 'errors' => [
                     'services' => ['The services field is required']
                 ]
             ], 422);
         }
         
         // Validate that the request contains an array field
         $validator = Validator::make($request->all(), [
             'services' => 'present|array',
         ]);
         
         if ($validator->fails()) {
             return response()->json([
                 'message' => 'Validation failed',
                 'errors' => $validator->errors()
             ], 422);
         }
 
         // Only validate category IDs if array is not empty
         if (count($request->services) > 0) {
             $validator = Validator::make($request->all(), [
                 'services.*' => 'exists:services,id'
             ]);
             
             if ($validator->fails()) {
                 return response()->json([
                     'message' => 'One or more service IDs are invalid',
                     'errors' => $validator->errors()
                 ], 422);
             }
             
            // Convert the array of service IDs into a collection
            $services = collect($request->services)->mapWithKeys(function ($serviceId) {
                return [$serviceId => []];
            });
        } else {
            // Empty array means we want to remove all services
            $services = [];
        }
 
         // The sync method:
         // 1. Detaches all services not in the provided array
         // 2. Attaches any new services from the array
         // 3. Updates pivot data for services that remain
         $booking->services()->sync($services);
 
         // Load the services with only id and name fields
         $booking->load(['services' => function($query) {
             $query->select('services.id', 'services.name');
         }]);
         
         // Format the response to only include necessary fields
         $formattedBooking = [
             'id' => $booking->id,
             'services' => $booking->services->map(function($service) {
                 return [
                     'id' => $service->id,
                     'name' => $service->name
                 ];
             })
         ];
 
         // Return a success response with the updated product and its categories
         return response()->json([
             'message' => 'Services synced successfully',
             'booking' => $formattedBooking
         ]);
    }

    /**
     * Get all services that are associated with at least one booking.
     *
     * @OA\Get(
     *     path="/api/booking-services",
     *     tags={"Bookings", "Services"},
     *     summary="Get all services used in bookings",
     *     description="Returns all services that are associated with at least one booking",
     *     @OA\Response(
     *         response=200,
     *         description="List of service names",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="string", example="Service Name")
     *         )
     *     )
     * )
     */
    public function bookingServices()
    {
        // Get all services that are associated with at least one booking
        // Only select id and name fields
        $servicesWithBookings = Service::select(['id', 'name'])
            ->whereHas('bookings')
            ->get();
        
        // Extract just the names as a simple array
        $serviceNames = $servicesWithBookings->pluck('name');
        
        return response()->json($serviceNames);
    }

}
