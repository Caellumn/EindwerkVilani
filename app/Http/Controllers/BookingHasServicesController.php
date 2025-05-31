<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use OpenApi\Annotations as OA;


class BookingHasServicesController extends Controller
{
    /**
     * Display the services for a specific booking.
     * 
     * @OA\Get(
     *     path="/api/bookings/{bookingId}/services",
     *     summary="Get services for a specific booking",
     *     description="Returns a list of services associated with a specific booking",
     *     operationId="getServicesByBooking",
     *     tags={"Booking Services"},
     *     @OA\Parameter(
     *         name="bookingId",
     *         in="path",
     *         description="ID of booking to get services for",
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
     *                 @OA\Property(property="name", type="string", example="Haircut")
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
     *     summary="Get all bookings with their associated services",
     *     description="Returns a list of all bookings with their associated services (id and name only)",
     *     operationId="getBookingsWithServices",
     *     tags={"Booking Services"},
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
     *                     property="services",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                         @OA\Property(property="name", type="string", example="Haircut")
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
     *     summary="Sync services for a booking",
     *     description="Replace all current services associated with a booking with the provided set. Send empty array to remove all services. Also recalculates booking end time based on total service duration.",
     *     operationId="syncBookingServices",
     *     tags={"Booking Services"},
     *     @OA\Parameter(
     *         name="bookingId",
     *         in="path",
     *         description="ID of booking to sync services for",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Array of service IDs to associate with the booking",
     *         @OA\JsonContent(
     *             required={"services"},
     *             @OA\Property(
     *                 property="services",
     *                 type="array",
     *                 description="Array of service IDs. Send empty array [] to remove all services.",
     *                 @OA\Items(type="string", format="uuid"),
     *                 example={"123e4567-e89b-12d3-a456-426614174000", "123e4567-e89b-12d3-a456-426614174001"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Services synced successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Services synced successfully"),
     *             @OA\Property(
     *                 property="booking",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(
     *                     property="services",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                         @OA\Property(property="name", type="string", example="Haircut")
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
     *             @OA\Property(property="message", type="string", example="The services field is required"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "services": {"The services field is required"},
     *                     "services.0": {"The selected services.0 is invalid."}
     *                 }
     *             )
     *         )
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

         // Recalculate booking end time based on total service duration
         $booking->recalculateEndTime();

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
     *     summary="Get all services used in bookings",
     *     description="Returns a list of service names that are associated with at least one booking",
     *     operationId="getServicesUsedInBookings",
     *     tags={"Booking Services"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="string",
     *                 example="Haircut"
     *             )
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
