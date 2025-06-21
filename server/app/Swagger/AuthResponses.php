<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="UserLoginSuccess",
 *     description="ログインに成功しました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="message", type="string", example="ログインに成功しました")
 *     )
 * )
 * 
 * @OA\Response(
 *     response="UserLogoutSuccess",
 *     description="ログアウトに成功しました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="message", type="string", example="ログアウトに成功しました")
 *     )
 * )
 * 
 * @OA\Response(
 *     response="UserRegisterSuccess",
 *     description="アカウント登録に成功しました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="message", type="string", example="アカウント登録に成功しました")
 *     )
 * )
 */
class AuthResponses {}
