<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\BookingService;
use Illuminate\Http\Request;

class BookingHasServicesController extends Controller
{
    /**
     * Display a listing of the resource.
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
     * Display the specified resource.
     */
    public function show()
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
     * Update the specified resource in storage.
     */
    public function syncServices(Request $request, Booking $bookingId)
    {
         // Find the product by ID
         $booking = Booking::find($bookingId);
        
         // Return 404 if product not found
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
                     'categories' => ['The categories field is required']
                 ]
             ], 422);
         }
         
         // Validate that the request contains an array field
         $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
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
             $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                 'services.*' => 'exists:services,id'
             ]);
             
             if ($validator->fails()) {
                 return response()->json([
                     'message' => 'One or more category IDs are invalid',
                     'errors' => $validator->errors()
                 ], 404);
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
             'message' => 'Categories synced successfully',
             'booking' => $formattedBooking
         ]);
    }

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
