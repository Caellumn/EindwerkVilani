<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use App\Models\Service;
use App\Http\Requests\BaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use OpenApi\Annotations as OA;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @OA\Get(
     *     path="/api/bookings",
     *     summary="Get bookings with optional filtering",
     *     description="Returns a list of bookings with optional filtering by date, name, gender, and status",
     *     operationId="getBookings",
     *     tags={"Bookings"},
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Filter by year",
     *         required=false,
     *         @OA\Schema(type="integer", example=2024)
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         description="Filter by month (1-12)",
     *         required=false,
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *     @OA\Parameter(
     *         name="day",
     *         in="query",
     *         description="Filter by day (1-31)",
     *         required=false,
     *         @OA\Schema(type="integer", example=25)
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Filter by name (partial match)",
     *         required=false,
     *         @OA\Schema(type="string", example="John")
     *     ),
     *     @OA\Parameter(
     *         name="gender",
     *         in="query",
     *         description="Filter by gender",
     *         required=false,
     *         @OA\Schema(type="string", enum={"male", "female"})
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by booking status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "confirmed", "cancelled", "completed"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="date", type="string", format="date-time", example="2024-12-25 10:00:00"),
     *                 @OA\Property(property="end_time", type="string", format="date-time", example="2024-12-25 11:00:00"),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="telephone", type="string", example="+31612345678"),
     *                 @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male"),
     *                 @OA\Property(property="remarks", type="string", example="First time customer"),
     *                 @OA\Property(property="status", type="string", enum={"pending", "confirmed", "cancelled", "completed"}, example="confirmed"),
     *                 @OA\Property(property="user_id", type="string", format="uuid", nullable=true),
     *                 @OA\Property(property="service_id", type="string", format="uuid", nullable=true),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="telephone", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid filter parameters",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="gender needs to be male or female")
     *         )
     *     )
     * )
     */
    public function index(BaseRequest $request)
    {
        // Validate parameters if present
        if ($request->has('gender') && !in_array($request->gender, ['male', 'female'])) {
            return response()->json(['error' => 'gender needs to be male or female'], 422);
        }
        
        if ($request->has('status') && !in_array($request->status, ['pending', 'confirmed', 'cancelled', 'completed'])) {
            return response()->json(['error' => 'status needs to be pending, confirmed, cancelled or completed'], 422);
        }
        
        // Initialize query
        $query = Booking::query();
        
        // Filter by date if parameters are provided
        if ($request->has('year')) {
            $query->whereYear('date', $request->year);
        }
        
        if ($request->has('month')) {
            $query->whereMonth('date', $request->month);
        }
        
        if ($request->has('day')) {
            $query->whereDay('date', $request->day);
        }
        
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        
        if ($request->has('gender')) {
            $query->where('gender', $request->gender);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Get bookings with user details
        $bookings = $query->with('user:id,name,email,telephone')->get();
        
        return response()->json($bookings);
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @OA\Post(
     *     path="/api/bookings",
     *     summary="Create a new booking",
     *     description="Creates a new booking with overlap detection and automatic end time calculation",
     *     operationId="createBooking",
     *     tags={"Bookings"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Booking creation data",
     *         @OA\JsonContent(
     *             required={"date", "name", "email", "telephone", "gender", "remarks", "status"},
     *             @OA\Property(property="date", type="string", format="date-time", example="2024-12-25 10:00:00", description="Booking start time (must be in the future). Also accepts format: 2024-12-25 10h00m"),
     *             @OA\Property(property="name", type="string", example="John Doe", description="Customer name"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="telephone", type="string", example="+31612345678"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male"),
     *             @OA\Property(property="remarks", type="string", example="First time customer"),
     *             @OA\Property(property="status", type="string", enum={"pending", "confirmed", "cancelled", "completed"}, example="pending"),
     *             @OA\Property(property="user_id", type="string", format="uuid", nullable=true, description="Optional existing user ID"),
     *             @OA\Property(property="service_id", type="string", format="uuid", nullable=true, description="Optional service ID (for automatic end time calculation)"),
     *             @OA\Property(property="end_time", type="string", format="date-time", nullable=true, example="2024-12-25 11:00:00", description="Optional end time (auto-calculated if service_id provided)"),
     *             @OA\Property(property="force_create", type="boolean", example=false, description="Set to true to bypass overlap warnings")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Booking created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="date", type="string", format="date-time", example="2024-12-25 10:00:00"),
     *             @OA\Property(property="end_time", type="string", format="date-time", example="2024-12-25 11:00:00"),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="telephone", type="string", example="+31612345678"),
     *             @OA\Property(property="gender", type="string", example="male"),
     *             @OA\Property(property="remarks", type="string", example="First time customer"),
     *             @OA\Property(property="status", type="string", example="pending")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Booking overlap detected",
     *         @OA\JsonContent(
     *             @OA\Property(property="warning", type="string", example="overlapping_booking"),
     *             @OA\Property(property="message", type="string", example="This booking overlaps with existing bookings for the same gender."),
     *             @OA\Property(
     *                 property="overlapping_bookings",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="start_time", type="string", example="10:00"),
     *                     @OA\Property(property="end_time", type="string", example="11:00"),
     *                     @OA\Property(property="date", type="string", example="25-12-2024")
     *                 )
     *             ),
     *             @OA\Property(property="continue_anyway", type="boolean", example=false)
     *         )
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
     *                     "date": {"The date must be a date after or equal to now."},
     *                     "email": {"The email must be a valid email address."},
     *                     "gender": {"The selected gender is invalid."}
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while creating the booking."),
     *             @OA\Property(property="error", type="string", example="Database connection failed")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            // WORKAROUND: Get data from JSON since request parsing is broken
            $jsonData = $request->json()->all();
            if (!empty($jsonData)) {
                // Merge JSON data into request
                $request->merge($jsonData);
            }
            
            // Convert custom date format if needed
            if ($request->has('date') && preg_match('/^\d{4}[-\/]\d{1,2}[-\/]\d{1,2} \d{1,2}h\d{1,2}m$/', $request->date)) {
                $dateParts = explode(' ', $request->date);
                $timeParts = str_replace(['h', 'm'], [':', ''], $dateParts[1]);
                
                // Process the date part to handle single digits
                $dateOnly = $dateParts[0];
                // Replace slashes with dashes
                $dateOnly = str_replace('/', '-', $dateOnly);
                
                // Parse the date to ensure proper formatting
                $dateComponents = explode('-', $dateOnly);
                if (count($dateComponents) === 3) {
                    $year = $dateComponents[0];
                    $month = str_pad($dateComponents[1], 2, '0', STR_PAD_LEFT);
                    $day = str_pad($dateComponents[2], 2, '0', STR_PAD_LEFT);
                    $dateOnly = "{$year}-{$month}-{$day}";
                }
                
                $request->merge(['date' => $dateOnly . ' ' . $timeParts . ':00']);
            }
            
            //validate the request
            $validated = $request->validate([
                'date' => 'required|date|after_or_equal:now',
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'telephone' => 'required|string|max:15',
                'gender' => 'required|in:male,female',
                'remarks' => 'required|string|max:255',
                'status' => 'required|in:pending,confirmed,cancelled,completed',
                'user_id' => 'sometimes|uuid|exists:users,id',
                'service_id' => 'sometimes|uuid|exists:services,id',
                'end_time' => 'sometimes|date|after_or_equal:now|after_or_equal:date',
                'force_create' => 'sometimes|boolean',

            ]);

            //if end time is not provided, calculate it based on the service time
            if (!$request->has('end_time')) {
                $service = Service::find($request->service_id);
                if ($service && $service->time) {
                    $end_time = Carbon::parse($request->date)->addMinutes((int) $service->time);
                    $validated['end_time'] = $end_time;
                } else {
                    // If no service found or service has no time, set end_time same as start time
                    $validated['end_time'] = Carbon::parse($request->date);
                }
            }

            // Check for overlapping bookings with same gender
            $startTime = Carbon::parse($validated['date']);
            $endTime = Carbon::parse($validated['end_time']);
            
            // Only check for overlaps if not forcing creation
            if (!$request->has('force_create') || !$request->force_create) {
                $overlappingBookings = Booking::where('gender', $validated['gender'])
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($query) use ($startTime, $endTime) {
                        $query->where(function ($q) use ($startTime, $endTime) {
                            // New booking start time overlaps with existing booking
                            $q->where('date', '<=', $startTime)
                              ->where('end_time', '>', $startTime);
                        })->orWhere(function ($q) use ($startTime, $endTime) {
                            // New booking end time overlaps with existing booking
                            $q->where('date', '<', $endTime)
                              ->where('end_time', '>=', $endTime);
                        })->orWhere(function ($q) use ($startTime, $endTime) {
                            // Existing booking is completely within new booking
                            $q->where('date', '>=', $startTime)
                              ->where('end_time', '<=', $endTime);
                        })->orWhere(function ($q) use ($startTime, $endTime) {
                            // New booking is completely within existing booking
                            $q->where('date', '<=', $startTime)
                              ->where('end_time', '>=', $endTime);
                        });
                    })
                    ->get(['id', 'name', 'date', 'end_time']);

                // If overlapping bookings found, return warning response
                if ($overlappingBookings->count() > 0) {
                    $overlappingDetails = $overlappingBookings->map(function ($booking) {
                        return [
                            'id' => $booking->id,
                            'name' => $booking->name,
                            'start_time' => Carbon::parse($booking->date)->format('H:i'),
                            'end_time' => Carbon::parse($booking->end_time)->format('H:i'),
                            'date' => Carbon::parse($booking->date)->format('d-m-Y')
                        ];
                    });

                    return response()->json([
                        'warning' => 'overlapping_booking',
                        'message' => 'This booking overlaps with existing bookings for the same gender.',
                        'overlapping_bookings' => $overlappingDetails,
                        'continue_anyway' => false
                    ], 409); // 409 Conflict status
                }
            }
                

            //create the booking
            $booking = Booking::create($validated);
            return response()->json($booking, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the booking.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * 
     * @OA\Get(
     *     path="/api/bookings/{id}",
     *     summary="Get booking by ID",
     *     description="Returns a single booking with user details",
     *     operationId="getBookingById",
     *     tags={"Bookings"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of booking to return",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="date", type="string", format="date-time", example="2024-12-25 10:00:00"),
     *             @OA\Property(property="end_time", type="string", format="date-time", example="2024-12-25 11:00:00"),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="telephone", type="string", example="+31612345678"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male"),
     *             @OA\Property(property="remarks", type="string", example="First time customer"),
     *             @OA\Property(property="status", type="string", enum={"pending", "confirmed", "cancelled", "completed"}, example="confirmed"),
     *             @OA\Property(property="user_id", type="string", format="uuid", nullable=true),
     *             @OA\Property(property="service_id", type="string", format="uuid", nullable=true),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 nullable=true,
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="telephone", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found"
     *     )
     * )
     */
    public function show(Booking $booking)
    {
        //get the booking with the user's name, email and telephone
        $booking = Booking::with('user:id,name,email,telephone')->find($booking->id);
        return response()->json($booking);
    }

    /**
     * Update the specified resource in storage.
     * 
     * @OA\Put(
     *     path="/api/bookings/{id}",
     *     summary="Update an existing booking",
     *     description="Updates a booking with validation. At least one field must be provided.",
     *     operationId="updateBooking",
     *     tags={"Bookings"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of booking to update",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="date", type="string", format="date-time", example="2024-12-25 10:00:00", description="Booking start time (must be in the future)"),
     *             @OA\Property(property="name", type="string", example="John Doe Updated"),
     *             @OA\Property(property="email", type="string", format="email", example="john.updated@example.com"),
     *             @OA\Property(property="telephone", type="string", example="+31612345679"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male"),
     *             @OA\Property(property="remarks", type="string", example="Updated remarks"),
     *             @OA\Property(property="status", type="string", enum={"pending", "confirmed", "cancelled", "completed"}, example="confirmed"),
     *             @OA\Property(property="user_id", type="string", format="uuid", nullable=true),
     *             @OA\Property(property="end_time", type="string", format="date-time", nullable=true, example="2024-12-25 11:00:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="date", type="string", format="date-time", example="2024-12-25 10:00:00"),
     *             @OA\Property(property="end_time", type="string", format="date-time", example="2024-12-25 11:00:00"),
     *             @OA\Property(property="name", type="string", example="John Doe Updated"),
     *             @OA\Property(property="email", type="string", example="john.updated@example.com"),
     *             @OA\Property(property="telephone", type="string", example="+31612345679"),
     *             @OA\Property(property="gender", type="string", example="male"),
     *             @OA\Property(property="remarks", type="string", example="Updated remarks"),
     *             @OA\Property(property="status", type="string", example="confirmed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking or User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="At least one field to update must be provided"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "fields": {"Please include at least one of: date, name, email, telephone, gender, remarks, status, end_time"},
     *                     "date": {"The date must be a date after or equal to now."},
     *                     "email": {"The email must be a valid email address."}
     *                 }
     *             )
     *         )
     *     )
     * )
     */
    public function update(Request $request, Booking $booking)
    {
        // Check if at least one valid field is provided
        if (!$request->hasAny(['date', 'name', 'email', 'telephone', 'gender', 'remarks', 'status', 'end_time'])) {
            return response()->json([
                'error' => 'At least one field to update must be provided',
                'errors' => [
                    'fields' => ['Please include at least one of: date, name, email, telephone, gender, remarks, status, end_time']
                ]
            ], 422);
        }

        // Check if the user exists
        if ($request->has('user_id') && !User::where('id', $request->user_id)->exists()) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Validate the request
        $request->validate([
            'date' => 'sometimes|date|after_or_equal:now',
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'telephone' => 'sometimes|string|max:15',
            'gender' => 'sometimes|in:male,female',
            'remarks' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:pending,confirmed,cancelled,completed',
            'user_id' => 'sometimes|uuid|exists:users,id',
            'end_time' => 'sometimes|date|after_or_equal:now|after_or_equal:date',
        ]);

        // Update the booking
        $booking->update($request->only([
            'date', 'name', 'email', 'telephone', 'gender', 'remarks', 'status', 'user_id', 'end_time'
        ]));
        
        // Return the updated booking
        return response()->json($booking, 200);
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @OA\Delete(
     *     path="/api/bookings/{id}",
     *     summary="Cancel a booking",
     *     description="Soft deletes a booking by setting status to 'cancelled'",
     *     operationId="cancelBooking",
     *     tags={"Bookings"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of booking to cancel",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking cancelled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking cancelled successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Booking not found")
     *         )
     *     )
     * )
     */
    public function destroy(Booking $booking)
    {
        //if no booking is found return an error with 404 status code   
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        //delete the booking by setting the status to cancelled
        $booking->status = 'cancelled';
        $booking->save();
        return response()->json(['message' => 'Booking cancelled successfully']);
    }

    /**
     * Store a newly created booking with products and/or services.
     * 
     * @OA\Post(
     *     path="/api/bookings/full-store",
     *     summary="Create a new booking with products and/or services",
     *     description="Creates a new booking with overlap detection and attaches products and/or services. At least one product or service must be provided.",
     *     operationId="fullStoreBooking",
     *     tags={"Bookings"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Booking creation data with products and/or services",
     *         @OA\JsonContent(
     *             required={"date", "name", "email", "telephone", "gender", "remarks", "status"},
     *             @OA\Property(property="date", type="string", format="date-time", example="2024-12-25 10:00:00", description="Booking start time (must be in the future). Also accepts format: 2024-12-25 10h00m"),
     *             @OA\Property(property="name", type="string", example="John Doe", description="Customer name"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="telephone", type="string", example="+31612345678"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male"),
     *             @OA\Property(property="remarks", type="string", example="First time customer"),
     *             @OA\Property(property="status", type="string", enum={"pending", "confirmed", "cancelled", "completed"}, example="pending"),
     *             @OA\Property(property="user_id", type="string", format="uuid", nullable=true, description="Optional existing user ID"),
     *             @OA\Property(property="end_time", type="string", format="date-time", nullable=true, example="2024-12-25 11:00:00", description="Optional end time (auto-calculated if services provided)"),
     *             @OA\Property(property="force_create", type="boolean", example=false, description="Set to true to bypass overlap warnings"),
     *             @OA\Property(
     *                 property="services",
     *                 type="array",
     *                 description="Array of service IDs. At least one service or product must be provided.",
     *                 @OA\Items(type="string", format="uuid"),
     *                 example={"123e4567-e89b-12d3-a456-426614174000", "123e4567-e89b-12d3-a456-426614174001"}
     *             ),
     *             @OA\Property(
     *                 property="products",
     *                 type="array",
     *                 description="Array of product IDs. At least one service or product must be provided.",
     *                 @OA\Items(type="string", format="uuid"),
     *                 example={"123e4567-e89b-12d3-a456-426614174002", "123e4567-e89b-12d3-a456-426614174003"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Booking created successfully with products and/or services",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="date", type="string", format="date-time", example="2024-12-25 10:00:00"),
     *             @OA\Property(property="end_time", type="string", format="date-time", example="2024-12-25 11:00:00"),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="telephone", type="string", example="+31612345678"),
     *             @OA\Property(property="gender", type="string", example="male"),
     *             @OA\Property(property="remarks", type="string", example="First time customer"),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(
     *                 property="services",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="products",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Booking overlap detected",
     *         @OA\JsonContent(
     *             @OA\Property(property="warning", type="string", example="overlapping_booking"),
     *             @OA\Property(property="message", type="string", example="This booking overlaps with existing bookings for the same gender."),
     *             @OA\Property(
     *                 property="overlapping_bookings",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="start_time", type="string", example="10:00"),
     *                     @OA\Property(property="end_time", type="string", example="11:00"),
     *                     @OA\Property(property="date", type="string", example="25-12-2024")
     *                 )
     *             ),
     *             @OA\Property(property="continue_anyway", type="boolean", example=false)
     *         )
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
     *                     "date": {"The date must be a date after or equal to now."},
     *                     "email": {"The email must be a valid email address."},
     *                     "services_or_products": {"At least one service or product must be provided."}
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while creating the booking."),
     *             @OA\Property(property="error", type="string", example="Database connection failed")
     *         )
     *     )
     * )
     */
    public function fullStore(Request $request)
    {
        try {
            // WORKAROUND: Get data from JSON since request parsing is broken
            $jsonData = $request->json()->all();
            if (!empty($jsonData)) {
                // Merge JSON data into request
                $request->merge($jsonData);
            }
            
            // Convert custom date format if needed
            if ($request->has('date') && preg_match('/^\d{4}[-\/]\d{1,2}[-\/]\d{1,2} \d{1,2}h\d{1,2}m$/', $request->date)) {
                $dateParts = explode(' ', $request->date);
                $timeParts = str_replace(['h', 'm'], [':', ''], $dateParts[1]);
                
                // Process the date part to handle single digits
                $dateOnly = $dateParts[0];
                // Replace slashes with dashes
                $dateOnly = str_replace('/', '-', $dateOnly);
                
                // Parse the date to ensure proper formatting
                $dateComponents = explode('-', $dateOnly);
                if (count($dateComponents) === 3) {
                    $year = $dateComponents[0];
                    $month = str_pad($dateComponents[1], 2, '0', STR_PAD_LEFT);
                    $day = str_pad($dateComponents[2], 2, '0', STR_PAD_LEFT);
                    $dateOnly = "{$year}-{$month}-{$day}";
                }
                
                $request->merge(['date' => $dateOnly . ' ' . $timeParts . ':00']);
            }

            // Validate that at least one service or product is provided
            if ((!$request->has('services') || empty($request->services)) && 
                (!$request->has('products') || empty($request->products))) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'services_or_products' => ['At least one service or product must be provided.']
                    ]
                ], 422);
            }
            
            // Validate the request
            $validated = $request->validate([
                'date' => 'required|date|after_or_equal:now',
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'telephone' => 'required|string|max:15',
                'gender' => 'required|in:male,female',
                'remarks' => 'required|string|max:255',
                'status' => 'required|in:pending,confirmed,cancelled,completed',
                'user_id' => 'sometimes|uuid|exists:users,id',
                'end_time' => 'sometimes|date|after_or_equal:now|after_or_equal:date',
                'force_create' => 'sometimes|boolean',
                'services' => 'sometimes|array',
                'services.*' => 'sometimes|uuid|exists:services,id',
                'products' => 'sometimes|array', 
                'products.*' => 'sometimes|uuid|exists:products,id',
            ]);

            // Calculate end time based on services if not provided
            if (!$request->has('end_time') && $request->has('services') && !empty($request->services)) {
                $totalServiceTime = (int) Service::whereIn('id', $request->services)->sum('time');
                
                if ($totalServiceTime > 0) {
                    $end_time = Carbon::parse($request->date)->addMinutes($totalServiceTime);
                    $validated['end_time'] = $end_time;
                } else {
                    // If no service time found, set end_time same as start time
                    $validated['end_time'] = Carbon::parse($request->date);
                }
            } else if (!$request->has('end_time')) {
                // If no services provided and no end_time, set end_time same as start time
                $validated['end_time'] = Carbon::parse($request->date);
            }

            // Check for overlapping bookings with same gender
            $startTime = Carbon::parse($validated['date']);
            $endTime = Carbon::parse($validated['end_time']);
            
            // Only check for overlaps if not forcing creation
            if (!$request->has('force_create') || !$request->force_create) {
                $overlappingBookings = Booking::where('gender', $validated['gender'])
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($query) use ($startTime, $endTime) {
                        $query->where(function ($q) use ($startTime, $endTime) {
                            // New booking start time overlaps with existing booking
                            $q->where('date', '<=', $startTime)
                              ->where('end_time', '>', $startTime);
                        })->orWhere(function ($q) use ($startTime, $endTime) {
                            // New booking end time overlaps with existing booking
                            $q->where('date', '<', $endTime)
                              ->where('end_time', '>=', $endTime);
                        })->orWhere(function ($q) use ($startTime, $endTime) {
                            // Existing booking is completely within new booking
                            $q->where('date', '>=', $startTime)
                              ->where('end_time', '<=', $endTime);
                        })->orWhere(function ($q) use ($startTime, $endTime) {
                            // New booking is completely within existing booking
                            $q->where('date', '<=', $startTime)
                              ->where('end_time', '>=', $endTime);
                        });
                    })
                    ->get(['id', 'name', 'date', 'end_time']);

                // If overlapping bookings found, return warning response
                if ($overlappingBookings->count() > 0) {
                    $overlappingDetails = $overlappingBookings->map(function ($booking) {
                        return [
                            'id' => $booking->id,
                            'name' => $booking->name,
                            'start_time' => Carbon::parse($booking->date)->format('H:i'),
                            'end_time' => Carbon::parse($booking->end_time)->format('H:i'),
                            'date' => Carbon::parse($booking->date)->format('d-m-Y')
                        ];
                    });

                    return response()->json([
                        'warning' => 'overlapping_booking',
                        'message' => 'This booking overlaps with existing bookings for the same gender.',
                        'overlapping_bookings' => $overlappingDetails,
                        'continue_anyway' => false
                    ], 409); // 409 Conflict status
                }
            }

            // Create the booking (exclude services and products from main creation)
            $bookingData = collect($validated)->except(['services', 'products', 'force_create'])->toArray();
            $booking = Booking::create($bookingData);

            // Attach services if provided
            if ($request->has('services') && !empty($request->services)) {
                $booking->services()->attach($request->services);
            }

            // Attach products if provided  
            if ($request->has('products') && !empty($request->products)) {
                $booking->products()->attach($request->products);
            }

            // Load the booking with its related services and products
            $booking->load([
                'services:id,name,time',
                'products:id,name,price'
            ]);

            // Format the response
            $response = [
                'id' => $booking->id,
                'date' => $booking->date,
                'end_time' => $booking->end_time,
                'name' => $booking->name,
                'email' => $booking->email,
                'telephone' => $booking->telephone,
                'gender' => $booking->gender,
                'remarks' => $booking->remarks,
                'status' => $booking->status,
                'user_id' => $booking->user_id,
                'services' => $booking->services->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'time' => $service->time
                    ];
                }),
                'products' => $booking->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->price
                    ];
                })
            ];

            return response()->json($response, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the booking.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
