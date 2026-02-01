<?php

namespace App\Swagger;

/**
 * ログインリクエスト
 *
 * @OA\Schema(
 *     schema="LoginRequest",
 *     required={"email", "password"},
 *     @OA\Property(property="email", type="string", format="email", example="test@example.com"),
 *     @OA\Property(property="password", type="string", format="password", example="password")
 * )
 *
 * ユーザー登録リクエスト
 *
 * @OA\Schema(
 *     schema="RegisterRequest",
 *     required={"name", "email", "password", "password_confirmation"},
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="test@example.com"),
 *     @OA\Property(property="password", type="string", format="password", example="password"),
 *     @OA\Property(property="password_confirmation", type="string", format="password", example="password")
 * )
 *
 * @OA\RequestBody(
 *     request="UserLoginRequest",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/LoginRequest")
 * )
 *
 * @OA\RequestBody(
 *     request="UserRegisterRequest",
 *     required=true,
 *     @OA\JsonContent(ref="#/components/schemas/RegisterRequest")
 * )
 */

class AuthRequests {}
