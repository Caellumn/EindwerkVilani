<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use App\Http\Requests\BaseRequest;
use App\Models\User;
use OpenApi\Annotations as OA;
use App\Models\Service;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @OA\Get(
     *     path="/api/bookings",
     *     summary="Get a list of bookings with optional filters",
     *     description="Returns a list of bookings. Can filter by date (year, month, day), name, gender, and status",
     *     operationId="getBookingsList",
     *     tags={"Bookings"},
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Filter bookings by year",
     *         required=false,
     *         @OA\Schema(type="integer", example=2023)
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         description="Filter bookings by month (1-12)",
     *         required=false,
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="day",
     *         in="query",
     *         description="Filter bookings by day of month (1-31)",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Filter bookings by customer name (partial match)",
     *         required=false,
     *         @OA\Schema(type="string", example="John")
     *     ),
     *     @OA\Parameter(
     *         name="gender",
     *         in="query",
     *         description="Filter bookings by customer gender (male or female only)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"male", "female"}, example="male")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter bookings by status (pending, confirmed, cancelled, or completed only)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "confirmed", "cancelled", "completed"}, example="pending")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Booking")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error for query parameters",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 description="Error message describing the validation failure",
     *                 example="status needs to be pending, confirmed, cancelled or completed"
     *             ),
     *             @OA\Examples(
     *                 example="status_error",
     *                 summary="Invalid status parameter",
     *                 value={"error": "status needs to be pending, confirmed, cancelled or completed or gender needs to be male or female"}
     *             ),
     *             @OA\Examples(
     *                 example="gender_error",
     *                 summary="Invalid gender parameter",
     *                 value={"error": "gender needs to be male or female"}
     *             )
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
     *     description="Creates a new booking with the provided information. Booking date must be in the future. Supports custom date formats like '2023-12-10 10h20m' or '2023/12/4 10h30m'. Automatically detects booking overlaps for the same gender and returns a warning that can be overridden with the force_create parameter.",
     *     operationId="createBooking",
     *     tags={"Bookings"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Booking data",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/BookingRequest"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="force_create",
     *                         type="boolean",
     *                         description="Set to true to create booking even when overlaps are detected",
     *                         example=false
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Booking created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Booking")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Booking overlap detected (same gender conflict)",
     *         @OA\JsonContent(ref="#/components/schemas/BookingOverlapResponse")
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
     *                     "gender": {"The selected gender is invalid."},
     *                     "email": {"The email field is required."}
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while creating the booking."),
     *             @OA\Property(property="error", type="string", example="Error details")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
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
     *     description="Returns a single booking by ID with associated user details",
     *     operationId="getBookingById",
     *     tags={"Bookings"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the booking to return",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Booking")
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
    public function show(Booking $booking)
    {
        //get the booking with the user's name, email and telephone
        $booking = Booking::with('user:id,name,email,telephone')->find($booking->id);
        return response()->json($booking);
    }

    /**
     * Update the specified resource in storage.
     * 
     * @OA\Patch(
     *     path="/api/bookings/{id}",
     *     summary="Update an existing booking",
     *     description="Updates a booking with the provided information. At least one field must be provided.",
     *     operationId="updateBooking",
     *     tags={"Bookings"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the booking to update",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Booking update data - at least one field must be provided",
     *         @OA\JsonContent(ref="#/components/schemas/BookingUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Booking")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking or User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Booking not found")
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
     *                 @OA\Property(
     *                     property="fields",
     *                     type="array",
     *                     @OA\Items(type="string", example="Please include at least one of: date, name, email, telephone, gender, remarks, status, end_time")
     *                 )
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
     *     description="Cancels a booking by setting its status to 'cancelled'",
     *     operationId="cancelBooking",
     *     tags={"Bookings"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the booking to cancel",
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
}
