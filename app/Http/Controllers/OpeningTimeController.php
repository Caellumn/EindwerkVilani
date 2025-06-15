<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OpeningTime;

/**
 * @OA\Tag(
 *     name="Opening Times",
 *     description="Hair salon opening hours and schedule management"
 * )
 */
class OpeningTimeController extends Controller
{

    /**
     * Get all opening times
     * 
     * @OA\Get(
     *     path="/api/opening-times",
     *     summary="Get salon opening hours",
     *     description="Retrieve the complete weekly schedule of the hair salon including opening and closing times for each day, or closed status for days when the salon is not open.",
     *     operationId="getOpeningTimes",
     *     tags={"Opening Times"},
     *     @OA\Response(
     *         response=200,
     *         description="Opening times retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1, description="Unique identifier for the opening time record"),
     *                 @OA\Property(property="day", type="string", example="monday", description="Day of the week", enum={"monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"}),
     *                 @OA\Property(property="status", type="string", example="open", description="Whether the salon is open or closed on this day", enum={"open", "gesloten"}),
     *                 @OA\Property(property="open", type="string", format="time", example="09:00:00", nullable=true, description="Opening time in HH:MM:SS format (null when closed)"),
     *                 @OA\Property(property="close", type="string", format="time", example="18:00:00", nullable=true, description="Closing time in HH:MM:SS format (null when closed)")
     *             ),
     *             example={
     *                 {
     *                     "id": 1,
     *                     "day": "monday",
     *                     "status": "open",
     *                     "open": "09:00:00",
     *                     "close": "18:00:00"
     *                 },
     *                 {
     *                     "id": 2,
     *                     "day": "tuesday",
     *                     "status": "gesloten",
     *                     "open": null,
     *                     "close": null
     *                 },
     *                 {
     *                     "id": 3,
     *                     "day": "wednesday", 
     *                     "status": "gesloten",
     *                     "open": null,
     *                     "close": null
     *                 },
     *                 {
     *                     "id": 4,
     *                     "day": "thursday",
     *                     "status": "open",
     *                     "open": "09:00:00",
     *                     "close": "18:00:00"
     *                 },
     *                 {
     *                     "id": 5,
     *                     "day": "friday",
     *                     "status": "open",
     *                     "open": "09:00:00",
     *                     "close": "18:00:00"
     *                 },
     *                 {
     *                     "id": 6,
     *                     "day": "saturday",
     *                     "status": "open",
     *                     "open": "09:00:00",
     *                     "close": "16:00:00"
     *                 },
     *                 {
     *                     "id": 7,
     *                     "day": "sunday",
     *                     "status": "open",
     *                     "open": "09:00:00",
     *                     "close": "13:00:00"
     *                 }
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving opening times")
     *         )
     *     )
     * )
     */
    public function getOpeningTimes()
    {
        try {
            $openingTimes = OpeningTime::orderBy('id')->get();
            $openingTimes->makeHidden(['updated_at', 'created_at']);
            return response()->json($openingTimes);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while retrieving opening times'
            ], 500);
        }
    }
}
