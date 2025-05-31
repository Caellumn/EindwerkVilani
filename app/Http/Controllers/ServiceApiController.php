<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class ServiceApiController extends Controller
{
    /**
     * Display a listing of active services.
     * 
     * @OA\Get(
     *     path="/api/services",
     *     summary="Get all active services",
     *     description="Returns a list of all active services (active = 1)",
     *     operationId="getActiveServices",
     *     tags={"Services"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="name", type="string", example="Haircut"),
     *                 @OA\Property(property="description", type="string", example="Professional haircut service"),
     *                 @OA\Property(property="hairlength", type="string", example="short"),
     *                 @OA\Property(property="price", type="number", format="float", example=25.00),
     *                 @OA\Property(property="time", type="integer", nullable=true, example=30),
     *                 @OA\Property(property="active", type="integer", example=1)
     *             )
     *         )
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
        $services = Service::where('active', 1)->get();
        
        if ($services->isEmpty()) {
            return response()->json(['message' => 'No services are active'], 404);
        }
        
        return response()->json($services, 200);
    }

    /**
     * Display all services regardless of status.
     * 
     * @OA\Get(
     *     path="/api/services/all",
     *     summary="Get all services",
     *     description="Returns a list of all services regardless of active status",
     *     operationId="getAllServices",
     *     tags={"Services"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="name", type="string", example="Haircut"),
     *                 @OA\Property(property="description", type="string", example="Professional haircut service"),
     *                 @OA\Property(property="hairlength", type="string", example="short"),
     *                 @OA\Property(property="price", type="number", format="float", example=25.00),
     *                 @OA\Property(property="time", type="integer", nullable=true, example=30),
     *                 @OA\Property(property="active", type="integer", example=1)
     *             )
     *         )
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
    public function allServices()
    {
        $services = Service::all();
        
        if ($services->isEmpty()) {
            return response()->json(['message' => 'No services are found'], 404);
        }
        
        return response()->json($services, 200);
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @OA\Post(
     *     path="/api/services",
     *     summary="Create a new service",
     *     description="Creates a new service and optionally associates it with categories",
     *     operationId="createService",
     *     tags={"Services"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Service creation data",
     *         @OA\JsonContent(
     *             required={"name", "description", "hairlength", "price"},
     *             @OA\Property(property="name", type="string", example="Haircut"),
     *             @OA\Property(property="description", type="string", example="Professional haircut service"),
     *             @OA\Property(property="hairlength", type="string", example="short", description="Hair length category"),
     *             @OA\Property(property="price", type="number", format="float", example=25.00),
     *             @OA\Property(property="categories", type="array", description="Optional array of category IDs", @OA\Items(type="string", format="uuid"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Service created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="name", type="string", example="Haircut"),
     *             @OA\Property(property="description", type="string", example="Professional haircut service"),
     *             @OA\Property(property="hairlength", type="string", example="short"),
     *             @OA\Property(property="price", type="number", format="float", example=25.00),
     *             @OA\Property(property="active", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Service already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service already exists")
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
     *     path="/api/services/{id}",
     *     summary="Get service by ID",
     *     description="Returns a single service",
     *     operationId="getServiceById",
     *     tags={"Services"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of service to return",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="name", type="string", example="Haircut"),
     *             @OA\Property(property="description", type="string", example="Professional haircut service"),
     *             @OA\Property(property="hairlength", type="string", example="short"),
     *             @OA\Property(property="price", type="number", format="float", example=25.00),
     *             @OA\Property(property="time", type="integer", nullable=true, example=30),
     *             @OA\Property(property="active", type="integer", example=1)
     *         )
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
     *     summary="Update an existing service",
     *     description="Updates a service and optionally syncs its categories",
     *     operationId="updateService",
     *     tags={"Services"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of service to update",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Haircut"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="hairlength", type="string", example="medium"),
     *             @OA\Property(property="price", type="number", format="float", example=30.00),
     *             @OA\Property(property="categories", type="array", description="Optional array of category IDs to sync", @OA\Items(type="string", format="uuid"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="name", type="string", example="Updated Haircut"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="hairlength", type="string", example="medium"),
     *             @OA\Property(property="price", type="number", format="float", example=30.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service not found"
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
     *     path="/api/services/{id}",
     *     summary="Soft delete a service",
     *     description="Soft deletes a service by setting active to 0",
     *     operationId="deleteService",
     *     tags={"Services"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of service to delete",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service 'Haircut' deleted successfully"),
     *             @OA\Property(
     *                 property="object",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="name", type="string", example="Haircut"),
     *                 @OA\Property(property="active", type="integer", example=0)
     *             )
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
