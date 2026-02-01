<?php

namespace App\Swagger;

/**
 * 認証ユーザー取得レスポンス（success, message, data: LoginUser）
 *
 * @OA\Schema(
 *     schema="UserResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="ユーザーを取得しました。"),
 *     @OA\Property(property="data", ref="#/components/schemas/LoginUser", description="認証ユーザー情報")
 * )
 *
 * グループユーザー一覧取得レスポンス（allOf: BaseApiIndexResponse + data: User[]）
 *
 * @OA\Schema(
 *     schema="GroupUserResponse",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/BaseApiIndexResponse"),
 *         @OA\Schema(
 *             required={"data"},
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 description="ユーザ一覧",
 *                 @OA\Items(ref="#/components/schemas/User")
 *             )
 *         )
 *     }
 * )
 *
 * @OA\Response(
 *     response="UserIndexSuccess",
 *     description="同じグループのユーザーを5件取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/GroupUserResponse")
 * )
 * 
 * @OA\Response(
 *     response="UserShowSuccess",
 *     description="認証ユーザー情報を取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/UserResponse")
 * )
 */
class UserResponses {}
