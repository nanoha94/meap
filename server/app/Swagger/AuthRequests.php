<?php

namespace App\Swagger;

/**
 * @OA\RequestBody(
 *     request="UserLoginRequest",
 *     required=true,
 *     @OA\JsonContent(
 *         required={"email", "password"},
 *         type="object",
 *         @OA\Property(property="email", type="string", format="email", example="test@example.com"),
 *         @OA\Property(property="password", type="string", format="password", example="password")
 *     )
 * )
 * 
 * @OA\RequestBody(
 *     request="UserRegisterRequest",
 *     required=true,
 *     @OA\JsonContent(
 *         required={"name", "email", "password", "password_confirmation"},
 *         type="object",
 *         @OA\Property(property="name", type="string", example="John Doe"),
 *         @OA\Property(property="email", type="string", format="email", example="test@example.com"),
 *         @OA\Property(property="password", type="string", format="password", example="password"),
 *         @OA\Property(property="password_confirmation", type="string", format="password", example="password")
 *     )
 * )
 */

class AuthRequests {}
