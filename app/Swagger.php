<?php

namespace App;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="Kapsalon Vilani API",
 *         version="1.0.0",
 *         description="API for managing a hair salon's services, products, and appointments",
 *         @OA\Contact(
 *             email="info@kapsalonvilani.com"
 *         )
 *     ),
 *     @OA\Server(
 *         url="/api",
 *         description="API Server"
 *     ),
 *     @OA\PathItem(path="/api/bookings/{bookingId}/products"),
 *     @OA\PathItem(path="/api/bookings-with-products"),
 *     @OA\PathItem(path="/api/bookings/{bookingId}/products/sync"),
 *     @OA\PathItem(path="/api/booking-products"),
 *     @OA\PathItem(path="/api/bookings/{bookingId}/services"),
 *     @OA\PathItem(path="/api/bookings-with-services"),
 *     @OA\PathItem(path="/api/bookings/{bookingId}/services/sync"),
 *     @OA\PathItem(path="/api/booking-services")
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
 *     @OA\Property(property="hairlength", type="string", enum={"short", "medium", "long"}, example="medium"),
 *     @OA\Property(property="price", type="number", format="decimal", example=25.50),
 *     @OA\Property(property="time", type="integer", example=30, description="Service duration in minutes"),
 *     @OA\Property(property="active", type="boolean", example=true, description="Service status"),
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
 *     @OA\Property(property="date", type="string", format="date-time", example="2023-06-15T14:30:00Z", description="Booking start date and time"),
 *     @OA\Property(property="end_time", type="string", format="date-time", example="2023-06-15T16:00:00Z", description="Booking end time (automatically calculated based on services)"),
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
 *     schema="BookingWithProducts",
 *     @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
 *     @OA\Property(
 *         property="products",
 *         type="array",
 *         @OA\Items(
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="Product Name")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="BookingWithServices",
 *     @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
 *     @OA\Property(
 *         property="services",
 *         type="array",
 *         @OA\Items(
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="Service Name")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="BookingProductRequest",
 *     required={"products"},
 *     @OA\Property(
 *         property="products",
 *         type="array",
 *         description="Array of product IDs to associate with the booking",
 *         @OA\Items(type="integer", example=1)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="BookingServiceRequest",
 *     required={"services"},
 *     @OA\Property(
 *         property="services",
 *         type="array",
 *         description="Array of service IDs to associate with the booking",
 *         @OA\Items(type="integer", example=1)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="BookingProductResponse",
 *     @OA\Property(property="message", type="string", example="Products synced successfully"),
 *     @OA\Property(
 *         property="booking",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(
 *             property="products",
 *             type="array",
 *             @OA\Items(
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="Product Name")
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="BookingServiceResponse",
 *     @OA\Property(property="message", type="string", example="Services synced successfully"),
 *     @OA\Property(
 *         property="booking",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(
 *             property="services",
 *             type="array",
 *             @OA\Items(
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="Service Name")
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ValidationError",
 *     @OA\Property(
 *         property="message", 
 *         type="string", 
 *         example="The products field is required",
 *         description="Error message describing the validation failure"
 *     ),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         description="Detailed validation errors",
 *         example={
 *             "products": {"The products field is required"},
 *             "products.0": {"The selected products.0 is invalid."}
 *         }
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="BookingRequest",
 *     required={"date", "name", "email", "telephone", "gender", "remarks", "status"},
 *     @OA\Property(property="date", type="string", format="date-time", example="2023-06-15T14:30:00Z", description="Booking start date and time (required)"),
 *     @OA\Property(property="end_time", type="string", format="date-time", example="2023-06-15T16:00:00Z", description="Booking end time (optional - automatically calculated from services if not provided)"),
 *     @OA\Property(property="name", type="string", example="John Doe", description="Customer name (required)"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Customer email (required)"),
 *     @OA\Property(property="telephone", type="string", example="+31612345678", description="Customer telephone (required)"),
 *     @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male", description="Customer gender (required)"),
 *     @OA\Property(property="remarks", type="string", example="First time customer, prefers short haircut", description="Additional remarks (required)"),
 *     @OA\Property(property="status", type="string", enum={"pending", "confirmed", "cancelled", "completed"}, example="pending", description="Booking status (required)"),
 *     @OA\Property(property="user_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000", description="User ID (optional)"),
 *     @OA\Property(property="service_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000", description="Service ID for end time calculation (optional)"),
 *     @OA\Property(property="force_create", type="boolean", example=false, description="Set to true to create booking even when overlaps are detected (optional)")
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
 *     schema="BookingOverlapResponse",
 *     description="Response when booking overlap is detected",
 *     @OA\Property(property="warning", type="string", example="overlapping_booking", description="Warning type identifier"),
 *     @OA\Property(property="message", type="string", example="This booking overlaps with existing bookings for the same gender.", description="Human-readable warning message"),
 *     @OA\Property(property="continue_anyway", type="boolean", example=false, description="Flag indicating if booking can be forced"),
 *     @OA\Property(
 *         property="overlapping_bookings",
 *         type="array",
 *         description="Array of conflicting bookings",
 *         @OA\Items(
 *             @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000", description="Booking ID"),
 *             @OA\Property(property="name", type="string", example="John Doe", description="Customer name"),
 *             @OA\Property(property="start_time", type="string", format="time", example="21:23", description="Booking start time"),
 *             @OA\Property(property="end_time", type="string", format="time", example="23:53", description="Booking end time"),
 *             @OA\Property(property="date", type="string", format="date", example="15-01-2025", description="Booking date")
 *         )
 *     )
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