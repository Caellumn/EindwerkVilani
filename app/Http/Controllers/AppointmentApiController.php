<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
//added manually
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Agenda;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;



class AppointmentApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //show all appointments with the appointment details
        //the user details and the service hashairlengths details
        $appointments = Appointment::with('user', 'service', 'hairlength','serviceWithHairlength')->get();
        return response()->json($appointments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
{
    $data = $request->validated();
    $data['status']  = 'pending';
    $data['user_id'] = auth()->id();      // will be null for guests

    // Wrap all DB operations in a transaction
    $appointment = DB::transaction(function () use ($data) {
        // 1. create appointment via mass assignment
        $appointment = Appointment::create($data);

        // 2. fetch matching agenda or throw validation exception (422)
        $agenda = Agenda::where('gender', $data['gender'])
                        ->firstOrFail(fn() => ValidationException::withMessages([
                            'gender' => "No stylist available for {$data['gender']} clients",
                        ]));

        // 3. attach pivot (agenda ↔ appointment) with extra user_id on pivot
        $agenda->appointments()
               ->attach($appointment->id, [
                   'user_id' => $data['user_id'] ?? 1,
               ]);

        return $appointment;
    });

    // 4. return a Resource (will be serialized to JSON, 201 status)
    return (new AppointmentResource(
                $appointment->load('serviceWithHairlength')
            ))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
}  // <-- Make sure this closing brace is present
    

    /**
     * Display the specified resource.
     */
    public function show(Appointment $appointment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Appointment $appointment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Appointment $appointment)
    {
        //
    }
}

