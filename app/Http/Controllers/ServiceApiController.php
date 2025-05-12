<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class ServiceApiController extends Controller
{
   /**
     * Display a listing of the resource.
     * 
     * @OA\Get(
     *     path="/services",
     *     tags={"Services"},
     *     summary="Get all services",
     *     description="Returns list of all services",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Service"))
     *     )
      * )
      */
    public function index()
    {
        // get all services where active is 1
        return Service::where('active', 1)->get();
    }

    /**
     * Store a newly created resource in storage.
     * 
      * @OA\Post(
      *     path="/services", 
     *     tags={"Services"},
      *     summary="Create a new service",
     *     description="Creates a new service and returns the service details",
      *     @OA\RequestBody(
      *         required=true,
     *         description="Required fields: name, description, duration_phase_1. Optional fields: rest_duration, duration_phase_2",
     *         @OA\JsonContent(
     *             required={"name", "description", "duration_phase_1"},
     *             @OA\Property(property="name", type="string", example="Haircut", description="Required. Name of the service"),
     *             @OA\Property(property="description", type="string", example="Basic haircut service", description="Required. Description of the service"),
     *             @OA\Property(property="duration_phase_1", type="integer", example=30, description="Required. Duration of phase 1 in minutes"),
     *             @OA\Property(property="rest_duration", type="integer", example=0, description="Optional. Rest duration in minutes"),
     *             @OA\Property(property="duration_phase_2", type="integer", example=0, description="Optional. Duration of phase 2 in minutes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Service")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Service already exists",
      *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service already exists")
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
        //create a new service
        $name = $request->name;
        $description = $request->description;
        $hairlength = $request->hairlength;
        $price = $request->price;
        
        //check if the product name is already in the database
        $service = Service::where('name', $name)->first();
        if ($service) {                                                     /*error code*/  
            return response()->json(['message' => 'Service already exists'], 400);
        }
        $service = Service::create([
            'name' => $name,
            'description' => $description,
            'hairlength' => $hairlength,
            'price' => $price,
        ]);

        // Attach categories if provided
        if ($request->has('categories')) {
            $categories = collect($request->categories)->mapWithKeys(function ($categoryId) {
                return [$categoryId => ['active' => 1]];
            });
            $service->categories()->attach($categories);
        }

        return $service;
    }

    /**
     * Display the specified resource.
     * 
     * @OA\Get(
     *     path="/services/{id}",
     *     tags={"Services"},
     *     summary="Get service by ID",
     *     description="Returns a single service",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of service to return",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Service")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service not found"
     *     )
     * )
     */
    public function show(Service $service)
    {
        //show me a single service in json format
        return $service;
    }

    /**
     * Update the specified resource in storage.
     * 
     * @OA\Put(
     *     path="/services/{id}",
     *     tags={"Services"},
     *     summary="Update an existing service",
     *     description="Updates a service and returns the updated service details",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of service to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Haircut"),
     *             @OA\Property(property="description", type="string", example="Updated haircut service"),
     *             @OA\Property(property="duration_phase_1", type="integer", example=45),
     *             @OA\Property(property="rest_duration", type="integer", example=5),
     *             @OA\Property(property="duration_phase_2", type="integer", example=15)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Service")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, Service $service)
    {
        //update a service
        $service->update($request->all());

        // Update categories if provided
        if ($request->has('categories')) {
            $categories = collect($request->categories)->mapWithKeys(function ($categoryId) {
                return [$categoryId => ['active' => 1]];
            });
            $service->categories()->sync($categories);
        }

        return $service;
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @OA\Delete(
     *     path="/services/{id}",
     *     tags={"Services"},
     *     summary="Delete a service",
     *     description="Deletes a service by changing active to 0",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of service to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service deleted successfully"),
     *             @OA\Property(property="object", type="object", ref="#/components/schemas/Service")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service not found")
     *         )
     *     )
     * )
     */
    public function destroy(Service $service)
    {
        //delete a service
        //return a message that the service has been deleted
        //use a soft delete by changing active to 0
        $name = $service->name;  // Access the name property of the service object
        if(!$service){
            return response()->json(['message' => 'Service not found'], 404);
        }
        $service->active = 0;
        $service->save();
        return response()->json([
            'message' => 'Service "' . $name . '" deleted successfully',
            'object' => $service
        ], 200);
    }
}
