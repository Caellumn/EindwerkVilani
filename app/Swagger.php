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
 * @OA\Schema(
 *     schema="Product",
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
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
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
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
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="name", type="string", example="Hair Care", description="Category name (required)"),
 *     @OA\Property(property="description", type="string", example="Hair care products"),
 *     @OA\Property(property="active", type="integer", example=1, description="Category status: 1 for active, 0 for inactive/deleted (optional, default: 1)"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="pivot",
 *         type="object",
 *         @OA\Property(property="product_id", type="integer", example=1),
 *         @OA\Property(property="category_id", type="integer", example=1),
 *         @OA\Property(property="active", type="integer", example=1, description="1 for active relationship, 0 for inactive")
 *     )
 * )
 */
class Swagger
{
}