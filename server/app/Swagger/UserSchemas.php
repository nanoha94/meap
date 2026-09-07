<?php

namespace App\Swagger;

/**
 * 認証ユーザー情報（自分自身のプロフィール）
 *
 * @OA\Schema(
 *     schema="LoginUser",
 *     required={"id", "avatar"},
 *     @OA\Property(property="id", type="string", description="ユーザーID", example="1234567890"),
 *     @OA\Property(property="name", type="string", nullable=true, description="表示名", example="山田太郎"),
 *     @OA\Property(property="email", type="string", format="email", nullable=true, description="メールアドレス", example="test@example.com"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true, description="メール認証日時", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="language", type="string", nullable=true, description="言語設定", example="ja"),
 *     @OA\Property(
 *         property="avatar",
 *         type="object",
 *         description="アバター情報",
 *         required={"seed"},
 *         @OA\Property(property="seed", type="string", description="アバター用シード", example="1234567890"),
 *         @OA\Property(property="image", ref="#/components/schemas/Image", nullable=true, description="アップロードされたアバター画像")
 *     )
 * )
 *
 * グループ内ユーザー情報（一覧用）
 *
 * @OA\Schema(
 *     schema="User",
 *     required={"id", "name", "language", "avatar"},
 *     @OA\Property(property="id", type="string", description="ユーザーID", example="1234567890"),
 *     @OA\Property(property="name", type="string", description="表示名", example="山田太郎"),
 *     @OA\Property(property="language", type="string", description="言語設定", example="ja"),
 *     @OA\Property(
 *         property="avatar",
 *         type="object",
 *         description="アバター情報",
 *         required={"seed"},
 *         @OA\Property(property="seed", type="string", description="アバター用シード", example="1234567890"),
 *         @OA\Property(property="image", ref="#/components/schemas/Image", nullable=true, description="アップロードされたアバター画像")
 *     )
 * )
 */
class UserSchemas {}
