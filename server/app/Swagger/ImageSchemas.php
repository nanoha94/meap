<?php

namespace App\Swagger;

/**
 * 画像1件（レシピサムネイル・手順画像・アップロード結果などで共通利用）
 *
 * @OA\Schema(
 *     schema="Image",
 *     @OA\Property(property="id", type="string", description="画像ID", example="1"),
 *     @OA\Property(property="src", type="string", description="画像パス", example="/storage/group_id/recipes/steps/filename.jpg"),
 *     @OA\Property(property="width", type="integer", description="画像幅", example=800),
 *     @OA\Property(property="height", type="integer", description="画像高さ", example=600)
 * )
 */
class ImageSchemas {}
