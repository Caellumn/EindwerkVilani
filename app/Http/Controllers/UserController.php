<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Kapsalon Vilani API",
 *     version="1.0.0",
 *     description="API for managing salon users, services, and appointments"
 * )
 */
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @OA\Get(
     *     path="/api/users",
     *     summary="Get a list of active users",
     *     description="Returns a list of all active users (status = 1)",
     *     operationId="getUsersList",
     *     tags={"Users"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *                 @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male"),
     *                 @OA\Property(property="telephone", type="string", example="+31612345678"),
     *                 @OA\Property(property="role", type="string", enum={"admin", "user"}, example="user")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        //get all users but only show the name, email, gender, telephone and only if status is 1
        $users = User::select('name', 'email', 'gender', 'telephone',"id")
                    ->where('status', 1)
                    ->get();

        // Return JSON response for API requests
        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @OA\Post(
     *     path="/api/users",
     *     summary="Create a new user",
     *     description="Creates a new user with the provided information and returns the created user details",
     *     operationId="createUser",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User creation data",
     *         @OA\JsonContent(
     *             required={"name", "email", "gender", "telephone", "password"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male"),
     *             @OA\Property(property="telephone", type="string", example="+31612345678"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="role", type="string", enum={"admin", "user"}, example="user", description="Optional field")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male"),
     *             @OA\Property(property="telephone", type="string", example="+31612345678"),
     *             @OA\Property(property="role", type="string", enum={"admin", "user"}, example="user"),
     *             @OA\Property(property="profile_photo_url", type="string", example="https://ui-avatars.com/api/?name=John+Doe&color=7F9CF5&background=EBF4FF")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Email already exists"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "email": {"The email has already been taken."},
     *                     "gender": {"The selected gender is invalid."},
     *                     "role": {"The selected role is invalid."},
     *                     "password": {"The password must be at least 8 characters."}
     *                 }
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        // WORKAROUND: Get data from JSON since request parsing is broken
        $jsonData = $request->json()->all();
        if (!empty($jsonData)) {
            // Merge JSON data into request
            $request->merge($jsonData);
        }
        
        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'gender' => 'required|in:male,female',
            'telephone' => 'required|string|max:20',
            'password' => 'required|string|min:8',
            'role' => 'sometimes|in:admin,user',
        ]);

        //check if email has already been used and if so return an error with 422 status code
        if (User::where('email', $request->email)->exists()) {
            return response()->json(['error' => 'Email already exists'], 422);
        }

        //check if role is admin or user if something else return error 422
        if ($request->has('role') && $request->role !== 'admin' && $request->role !== 'user') {
            return response()->json(['error' => 'Role must be admin or user'], 422);
        }

        //save a new user when giving the info name,email,gender,telephone,password
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'gender' => $request->gender,
            'telephone' => $request->telephone,
            'password' => Hash::make($request->password),
            //role is optional
            'role' => $request->role ?? 'user',
            'status' => 1,
        ]);

        //only show specific fields in the response
        $user = $user->only(['id', 'name', 'email', 'gender', 'telephone', 'role', 'profile_photo_url']);
        
        return response()->json($user, 201);
    }

    /**
     * Display the specified resource.
     * 
     * @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Get user by ID",
     *     description="Returns a single user's details by their ID",
     *     operationId="getUserById",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the user to return",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male"),
     *             @OA\Property(property="telephone", type="string", example="+31612345678"),
     *             @OA\Property(property="role", type="string", enum={"admin", "user"}, example="user")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function show(User $user)
    {
        //get a user by id and show the name, email, gender, telephone, role
        $user = User::find($user->id);

        //if user is not found or status is not 1 return an error with 404 status code
        if (!$user || $user->status != 1) {
            return response()->json(['error' => 'User not found'], 404);
        }

        //now filter the name, email, gender, telephone, role
        $user = User::select('name', 'email', 'gender', 'telephone', 'role')
                ->where('id', $user->id)
                ->where('status', 1)
                ->first();

        // Return JSON response
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     * 
     * @OA\Patch(
     *     path="/api/users/{id}",
     *     summary="Update an existing user",
     *     description="Updates an existing user's information with the provided details. At least one field must be provided.",
     *     operationId="updateUser",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the user to update",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="User update data - at least one field must be provided",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe", description="Optional"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Optional"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male", description="Optional"),
     *             @OA\Property(property="telephone", type="string", example="+31612345678", description="Optional"),
     *             @OA\Property(property="role", type="string", enum={"admin", "user"}, example="user", description="Optional")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", format="uuid", example="0196df00-946c-7373-afef-4c7a76752aa3"),
     *             @OA\Property(property="name", type="string", example="testing23"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe34@example.com"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male"),
     *             @OA\Property(property="telephone", type="string", example="+31612345678"),
     *             @OA\Property(property="role", type="string", enum={"admin", "user"}, example="user"),
     *             @OA\Property(property="profile_photo_url", type="string", example="https://ui-avatars.com/api/?name=t&color=7F9CF5&background=EBF4FF")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="error", type="string", example="At least one field to update must be provided"),
     *                     @OA\Property(
     *                         property="errors",
     *                         type="object",
     *                         @OA\Property(
     *                             property="fields",
     *                             type="array",
     *                             @OA\Items(type="string", example="Please include at least one of: name, email, gender, telephone, role")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="error", type="string", example="The email has already been taken"),
     *                     @OA\Property(
     *                         property="errors",
     *                         type="object",
     *                         example={
     *                             "email": {"The email has already been taken."},
     *                             "gender": {"The selected gender is invalid."},
     *                             "role": {"The selected role is invalid."}
     *                         }
     *                     )
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function update(Request $request, User $user)
    {
        // Check if user exists and has status 1
        if (!$user || $user->status != 1) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Check if at least one valid field is provided
        if (!$request->hasAny(['name', 'email', 'gender', 'telephone', 'role'])) {
            return response()->json([
                'error' => 'At least one field to update must be provided',
                'errors' => [
                    'fields' => ['Please include at least one of: name, email, gender, telephone, role']
                ]
            ], 422);
        }

        // Validate the request
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'gender' => 'sometimes|in:male,female',
            'telephone' => 'sometimes|string|max:20',
            'role' => 'sometimes|in:admin,user',
        ]);

        // Update only the allowed fields
        $user->update($request->only([
            'name', 'email', 'gender', 'telephone', 'role'
        ]));
        
        //make hidden the updated, created and email verified at
        $user->makeHidden(['updated_at', 'created_at', 'email_verified_at','two_factor_confirmed_at','current_team_id','profile_photo_path','status']);  
        
        //with status 200 return the user
        return response()->json($user, 200);
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Soft delete a user",
     *     description="Soft deletes a user by setting their status to 0 (inactive)",
     *     operationId="deleteUser",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the user to delete",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User successfully deleted (status changed to inactive)",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User successfully deleted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function destroy(User $user)
    {
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        //change the status of the user to 0
        $user->status = 0;
        $user->save();
        
        return response()->json(['message' => 'User successfully deleted']);
    }
}
