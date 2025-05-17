<?php

namespace App;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Kapsalon Vilani API",
 *     version="1.0.0",
 *     description="API for managing a hair salon's services, products, and appointments",
 *     @OA\Contact(
 *         email="info@kapsalonvilani.com"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="/api",
 *     description="API Server"
 * )
 * 
 * @OA\Tag(
 *     name="Products",
 *     description="Product management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Services",
 *     description="Service management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Product Categories",
 *     description="API Endpoints for managing product categories relationships"
 * )
 * 
 * @OA\Tag(
 *     name="Users",
 *     description="User management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Bookings",
 *     description="Booking management endpoints"
 * )
 * 
 * @OA\Schema(
 *     schema="Product",
 *     @OA\Property(property="id", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
 *     @OA\Property(property="name", type="string", example="Shampoo"),
 *     @OA\Property(property="description", type="string", example="Something to wash your hair"),
 *     @OA\Property(property="price", type="number", format="float", example=9.99),
 *     @OA\Property(property="stock", type="integer", example=50),
 *     @OA\Property(property="image", type="string", example="shampoo.jpg"),
 *     @OA\Property(property="active", type="integer", example=1, description="1 for active product, 0 for inactive/deleted"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Service",
 *     @OA\Property(property="id", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
 *     @OA\Property(property="name", type="string", example="Haircut"),
 *     @OA\Property(property="description", type="string", example="Basic haircut service"),
 *     @OA\Property(property="duration_phase_1", type="integer", example=30),
 *     @OA\Property(property="rest_duration", type="integer", example=0),
 *     @OA\Property(property="duration_phase_2", type="integer", example=0),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Category",
 *     @OA\Property(property="id", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
 *     @OA\Property(property="name", type="string", example="Hair Care", description="Category name (required)"),
 *     @OA\Property(property="description", type="string", example="Hair care products"),
 *     @OA\Property(property="active", type="integer", example=1, description="Category status: 1 for active, 0 for inactive/deleted (optional, default: 1)"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="pivot",
 *         type="object",
 *         @OA\Property(property="product_id", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
 *         @OA\Property(property="category_id", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
 *         @OA\Property(property="active", type="integer", example=1, description="1 for active relationship, 0 for inactive")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male"),
 *     @OA\Property(property="telephone", type="string", example="+31612345678"),
 *     @OA\Property(property="role", type="string", enum={"admin", "user"}, example="user"),
 *     @OA\Property(property="status", type="integer", example=1, description="1 for active user, 0 for inactive/deleted"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Booking",
 *     @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
 *     @OA\Property(property="date", type="string", format="date-time", example="2023-06-15T14:30:00Z"),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="telephone", type="string", example="+31612345678"),
 *     @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male"),
 *     @OA\Property(property="remarks", type="string", example="First time customer, prefers short haircut"),
 *     @OA\Property(property="status", type="string", enum={"pending", "confirmed", "cancelled", "completed"}, example="pending"),
 *     @OA\Property(property="user_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
 *     @OA\Property(
 *         property="user",
 *         type="object",
 *         @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
 *         @OA\Property(property="name", type="string", example="John Doe"),
 *         @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *         @OA\Property(property="telephone", type="string", example="+31612345678")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="BookingRequest",
 *     required={"date", "name", "email", "telephone", "gender", "remarks", "status", "user_id"},
 *     @OA\Property(property="date", type="string", format="date-time", example="2023-06-15T14:30:00Z", description="Booking date and time (required)"),
 *     @OA\Property(property="name", type="string", example="John Doe", description="Customer name (required)"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Customer email (required)"),
 *     @OA\Property(property="telephone", type="string", example="+31612345678", description="Customer telephone (required)"),
 *     @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male", description="Customer gender (required)"),
 *     @OA\Property(property="remarks", type="string", example="First time customer, prefers short haircut", description="Additional remarks (required)"),
 *     @OA\Property(property="status", type="string", enum={"pending", "confirmed", "cancelled", "completed"}, example="pending", description="Booking status (required)"),
 *     @OA\Property(property="user_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000", description="User ID (required)")
 * )
 *
 * @OA\Schema(
 *     schema="BookingUpdateRequest",
 *     @OA\Property(property="date", type="string", format="date-time", example="2023-06-15T14:30:00Z", description="Booking date and time"),
 *     @OA\Property(property="name", type="string", example="John Doe", description="Customer name"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Customer email"),
 *     @OA\Property(property="telephone", type="string", example="+31612345678", description="Customer telephone"),
 *     @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male", description="Customer gender"),
 *     @OA\Property(property="remarks", type="string", example="First time customer, prefers short haircut", description="Additional remarks"),
 *     @OA\Property(property="status", type="string", enum={"pending", "confirmed", "cancelled", "completed"}, example="confirmed", description="Booking status"),
 *     @OA\Property(property="user_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000", description="User ID")
 * )
 *
 * @OA\Schema(
 *     schema="UserRequest",
 *     required={"name", "email", "gender", "telephone", "password"},
 *     @OA\Property(property="name", type="string", example="John Doe", description="User's full name (required)"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="User's email address (required, must be unique)"),
 *     @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male", description="User's gender (required)"),
 *     @OA\Property(property="telephone", type="string", example="+31612345678", description="User's telephone number (required)"),
 *     @OA\Property(property="password", type="string", format="password", example="password123", description="User's password (required, min 8 characters)"),
 *     @OA\Property(property="role", type="string", enum={"admin", "user"}, example="user", description="User's role (optional, default: user)")
 * )
 *
 * @OA\Schema(
 *     schema="UserUpdateRequest",
 *     @OA\Property(property="name", type="string", example="John Doe", description="User's full name (optional)"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="User's email address (optional, must be unique)"),
 *     @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male", description="User's gender (optional)"),
 *     @OA\Property(property="telephone", type="string", example="+31612345678", description="User's telephone number (optional)"),
 *     @OA\Property(property="role", type="string", enum={"admin", "user"}, example="user", description="User's role (optional)")
 * )
 *
 * @OA\Schema(
 *     schema="Error",
 *     @OA\Property(property="error", type="string", example="User not found")
 * )
 */
class Swagger
{
}