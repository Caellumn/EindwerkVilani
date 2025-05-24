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
     *     description="Returns list of all active services",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Service"))
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No active services found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No services are active")
     *         )
     *     )
      * )
      */
    public function index()
    {
        
        //if no services are active, return an error
        if(Service::where('active', 1)->count() == 0){
            return response()->json(['message' => 'No services are active'], 404);
        }
        // get all services where active is 1
        return Service::where('active', 1)->get();
    }

    /**
     * Display a listing of all services.
     * 
     * @OA\Get(
     *     path="/services/all",
     *     tags={"Services"},
     *     summary="Get all services (including inactive)",
     *     description="Returns list of all services regardless of active status",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Service"))
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No services found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No services are found")
     *         )
     *     )
     * )
     */
    public function indexAll(){

        //if not services are found return an error
        if(Service::all()->count() == 0){
            return response()->json(['message' => 'No services are found'], 404);
        }
        return Service::all();
    }   

    /**
     * Store a newly created resource in storage.
     * 
     * @OA\Post(
     *     path="/api/services", 
     *     tags={"Services"},
     *     summary="Create a new service",
     *     description="Creates a new service with duration-based pricing and returns the service details",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Required fields: name, description, hairlength, price, time. Optional fields: active",
     *         @OA\JsonContent(
     *             required={"name", "description", "hairlength", "price", "time"},
     *             @OA\Property(property="name", type="string", example="Haircut", description="Required. Name of the service"),
     *             @OA\Property(property="description", type="string", example="Basic haircut service", description="Required. Description of the service"),
     *             @OA\Property(property="hairlength", type="string", enum={"short", "medium", "long"}, example="medium", description="Required. Target hair length category"),
     *             @OA\Property(property="price", type="number", format="decimal", example=25.50, description="Required. Service price in euros"),
     *             @OA\Property(property="time", type="integer", example=30, description="Required. Service duration in minutes (1-480)"),
     *             @OA\Property(property="active", type="boolean", example=true, description="Optional. Service status (default: true)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Service created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Service")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Service already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service already exists")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The time field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "name": {"The name field is required."},
     *                     "hairlength": {"The selected hairlength is invalid."},
     *                     "time": {"The time must be between 1 and 480."}
     *                 }
     *             )
     *         )
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
     *         @OA\Schema(type="string")
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
     *     path="/api/services/{id}",
     *     tags={"Services"},
     *     summary="Update an existing service",
     *     description="Updates a service and returns the updated service details. All fields are optional for updates.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of service to update",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Service update data - all fields are optional",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Haircut", description="Service name"),
     *             @OA\Property(property="description", type="string", example="Updated haircut service description", description="Service description"),
     *             @OA\Property(property="hairlength", type="string", enum={"short", "medium", "long"}, example="long", description="Target hair length category"),
     *             @OA\Property(property="price", type="number", format="decimal", example=35.00, description="Service price in euros"),
     *             @OA\Property(property="time", type="integer", example=45, description="Service duration in minutes (1-480)"),
     *             @OA\Property(property="active", type="boolean", example=false, description="Service status")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Service")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Service not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The selected hairlength is invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "hairlength": {"The selected hairlength is invalid."},
     *                     "time": {"The time must be between 1 and 480."}
     *                 }
     *             )
     *         )
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
     *         @OA\Schema(type="string")
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
