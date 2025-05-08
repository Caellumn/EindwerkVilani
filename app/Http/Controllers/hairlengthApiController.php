<?php

namespace App\Http\Controllers;

use App\Models\Hairlength;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class hairlengthApiController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @OA\Get(
     *     path="/hairlengths",
     *     tags={"Hairlengths"},
     *     summary="Get all hairlengths",
     *     description="Returns list of all hairlengths",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Hairlength"))
     *     )
     * )
     */
    public function index()
    {
        //return all hairlengths
        return Hairlength::all();
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @OA\Post(
     *     path="/hairlengths",
     *     tags={"Hairlengths"},
     *     summary="Create a new hairlength",
     *     description="Creates a new hairlength and returns the hairlength details",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Required field: length",
     *         @OA\JsonContent(
     *             required={"length"},
     *             @OA\Property(property="length", type="string", example="Short", description="Required. Length of the hair (e.g., Short, Medium, Long)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Hairlength created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Hairlength")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        //create a new hairlength
        $hairlength = Hairlength::create($request->all());
        return response()->json($hairlength, 201);
    }

    /**
     * Display the specified resource.
     * 
     * @OA\Get(
     *     path="/hairlengths/{id}",
     *     tags={"Hairlengths"},
     *     summary="Get hairlength by ID",
     *     description="Returns a single hairlength",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of hairlength to return",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Hairlength")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Hairlength not found"
     *     )
     * )
     */
    public function show(Hairlength $hairlength)
    {
        //show me a single hairlength in json format
        return $hairlength;
    }

    /**
     * Update the specified resource in storage.
     * 
     * @OA\Put(
     *     path="/hairlengths/{id}",
     *     tags={"Hairlengths"},
     *     summary="Update an existing hairlength",
     *     description="Updates a hairlength and returns the updated hairlength details",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of hairlength to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="length", type="string", example="Medium")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Hairlength updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Hairlength")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Hairlength not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, Hairlength $hairlength)
    {
        //update a hairlength
        $hairlength->update($request->all());
        return $hairlength;
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @OA\Delete(
     *     path="/hairlengths/{id}",
     *     tags={"Hairlengths"},
     *     summary="Delete a hairlength",
     *     description="Deletes a hairlength",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of hairlength to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Hairlength deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Hairlength not found"
     *     )
     * )
     */
    public function destroy(Hairlength $hairlength)
    {
        //delete a hairlength
        $hairlength->delete();
        return response()->json(null, 204);
    }
}
