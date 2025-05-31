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
 */

class AuthRequests {}
