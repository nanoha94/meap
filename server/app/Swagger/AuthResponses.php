<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="UserLoginSuccess",
 *     description="ログインに成功しました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="ログインに成功しました"),
 *         @OA\Property(property="data", type="null", example=null)
 *     )
 * )
 * 
 * @OA\Response(
 *     response="UserLogoutSuccess",
 *     description="ログアウトに成功しました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="ログアウトに成功しました"),
 *         @OA\Property(property="data", type="null", example=null)
 *     )
 * )
 * 
 * @OA\Response(
 *     response="UserRegisterSuccess",
 *     description="アカウント登録に成功しました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="アカウント登録に成功しました"),
 *         @OA\Property(property="data", type="null", example=null)
 *     )
 * )
 * 
 * @OA\Response(
 *     response="UserAlreadyLoggedIn",
 *     description="既にログインしています",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="既にログインしています"),
 *         @OA\Property(property="data", type="null", example=null)
 *     )
 * )
 */
class AuthResponses {}
