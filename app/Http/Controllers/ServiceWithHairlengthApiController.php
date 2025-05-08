<?php

namespace App\Http\Controllers;

use App\Models\ServiceWithHairlength;
use Illuminate\Http\Request;

class ServiceWithHairlengthApiController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @OA\Get(
     *     path="/serviceswithhairlengths",
     *     tags={"Services with Hairlength"},
     *     summary="Get all services with their hairlengths",
     *     description="Returns list of all services with their corresponding hairlengths and prices",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="service_id", type="integer", example=1),
     *                 @OA\Property(property="hairlength_id", type="integer", example=1),
     *                 @OA\Property(property="price", type="number", format="float", example=25.00),
     *                 @OA\Property(
     *                     property="service",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Haircut"),
     *                     @OA\Property(property="description", type="string", example="Basic haircut service"),
     *                     @OA\Property(property="duration_phase_1", type="integer", example=30),
     *                     @OA\Property(property="rest_duration", type="integer", example=0),
     *                     @OA\Property(property="duration_phase_2", type="integer", example=0)
     *                 ),
     *                 @OA\Property(
     *                     property="hairlength",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="length", type="string", example="Short")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        //show all services with hairlengths with the servicename and hairlength length where status is 1
        return ServiceWithHairlength::with('service', 'hairlength')->where('status', 1)->get();
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @OA\Post(
     *     path="/serviceswithhairlengths",
     *     tags={"Services with Hairlength"},
     *     summary="Create a new service-hairlength combination",
     *     description="Creates a new service-hairlength combination and returns the details",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Required fields: service_id, hairlength_id, price",
     *         @OA\JsonContent(
     *             required={"service_id", "hairlength_id", "price"},
     *             @OA\Property(property="service_id", type="integer", example=1, description="Required. ID of the service"),
     *             @OA\Property(property="hairlength_id", type="integer", example=1, description="Required. ID of the hairlength"),
     *             @OA\Property(property="price", type="number", format="float", example=25.00, description="Required. Price for this service-hairlength combination")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Service-hairlength combination created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ServiceWithHairlength")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Service-hairlength combination already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This service-hairlength combination already exists"),
     *             @OA\Property(property="object", ref="#/components/schemas/ServiceWithHairlength")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'service_id' => 'required|exists:services,id',
                'hairlength_id' => 'required|exists:hairlengths,id',
                'price' => 'required|numeric|min:0'
            ]);

            //check if the service and hair length combination already exists
            $existingCombination = ServiceWithHairlength::where('service_id', $request->service_id)
                ->where('hairlength_id', $request->hairlength_id)
                ->first();

            if ($existingCombination) {
                return response()->json([
                    'message' => 'This service-hairlength combination already exists',
                    'object' => $existingCombination->load(['service', 'hairlength'])
                ], 409);
            }

            //store a new service-hairlength combination
            $serviceWithHairlength = ServiceWithHairlength::create($validated);
            // Load the relationships
            $serviceWithHairlength->load(['service', 'hairlength']);
            return response()->json([
                'message' => 'Service-hairlength combination created successfully',
                'object' => $serviceWithHairlength
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     * 
     * @OA\Get(
     *     path="/serviceswithhairlengths/{id}",
     *     tags={"Services with Hairlength"},
     *     summary="Get a specific service-hairlength combination",
     *     description="Returns a single service-hairlength combination with service and hairlength details",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the service-hairlength combination",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/ServiceWithHairlength")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service-hairlength combination not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service-hairlength combination not found")
     *         )
     *     )
     * )
     */
    public function show(ServiceWithHairlength $serviceWithHairlength)
    {
        //show the service length with service name and hair length
        return ServiceWithHairlength::with('service', 'hairlength')->find($serviceWithHairlength->id);
    }

    /**
     * Update the specified resource in storage.
     * 
     * @OA\Put(
     *     path="/serviceswithhairlengths/{id}",
     *     summary="Update a service-hairlength combination",
     *     description="Updates an existing service-hairlength combination with the specified price",
     *     operationId="updateServiceWithHairlength",
     *     tags={"Services with Hairlength"},
     *     @OA\Parameter(
     *         name="id",
     *         description="ID of the service-hairlength combination",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"service_id", "hairlength_id", "price"},
     *             @OA\Property(property="service_id", type="integer", example=1),
     *             @OA\Property(property="hairlength_id", type="integer", example=1),
     *             @OA\Property(property="price", type="number", format="float", example=25.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service-hairlength combination updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="service_id", type="integer", example=1),
     *             @OA\Property(property="hairlength_id", type="integer", example=1),
     *             @OA\Property(property="price", type="number", format="float", example=25.00),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(
     *                 property="service",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Haircut"),
     *                 @OA\Property(property="description", type="string", example="Basic haircut service"),
     *                 @OA\Property(property="duration_phase_1", type="integer", example=30),
     *                 @OA\Property(property="rest_duration", type="integer", example=15),
     *                 @OA\Property(property="duration_phase_2", type="integer", example=30)
     *             ),
     *             @OA\Property(
     *                 property="hairlength",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="length", type="string", example="Short")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service-hairlength combination not found"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Service-hairlength combination already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This service-hairlength combination already exists")
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
     *                 @OA\Property(
     *                     property="service_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="The service id field is required.")
     *                 ),
     *                 @OA\Property(
     *                     property="hairlength_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="The hairlength id field is required.")
     *                 ),
     *                 @OA\Property(
     *                     property="price",
     *                     type="array",
     *                     @OA\Items(type="string", example="The price field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function update(Request $request, ServiceWithHairlength $serviceWithHairlength)
    {
        try {
            //update the service-hairlength combination and validate the request
            $validated = $request->validate([
                'service_id' => 'required|exists:services,id',
                'hairlength_id' => 'required|exists:hairlengths,id',
                'price' => 'required|numeric|min:0'
            ]);

            //check if the service and hair length combination already exists
            $existingCombination = ServiceWithHairlength::where('service_id', $request->service_id)
                ->where('hairlength_id', $request->hairlength_id)
                ->first();

            if ($existingCombination) {
                return response()->json([
                    'message' => 'This service-hairlength combination already exists',
                    'object' => $existingCombination->load(['service', 'hairlength'])
                ], 409);
            }

            //update the service-hairlength combination
            $serviceWithHairlength->update($validated);
            return response()->json([
                'message' => 'Service-hairlength combination updated successfully',
                'object' => $serviceWithHairlength->load(['service', 'hairlength'])
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @OA\Delete(
     *     path="/serviceswithhairlengths/{id}",
     *     tags={"Services with Hairlength"},
     *     summary="Delete a service-hairlength combination",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the service-hairlength combination",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Service-hairlength combination deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service-hairlength combination not found"
     *     )
     * )
     */
    public function destroy(ServiceWithHairlength $serviceWithHairlength)
    {
        //delete the service-hairlength combination
        // mention the service name and hair length
        $serviceWithHairlength->delete();
        return response()->json([
            'message' => 'Service-hairlength combination deleted successfully',
            'object' => $serviceWithHairlength->load(['service', 'hairlength'])
        ], 200);
    }
}
