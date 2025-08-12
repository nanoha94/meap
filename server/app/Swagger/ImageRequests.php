<?php

namespace App\Swagger;

/**
 * @OA\RequestBody(
 *     request="ImageUploadBulkRequest",
 *     required=true,
 *     @OA\MediaType(
 *         mediaType="multipart/form-data",
 *         @OA\Schema(
 *             required={"images"},
 *             @OA\Property(
 *                 property="images",
 *                 type="array",
 *                 @OA\Items(type="string", format="binary"),
 *                 description="アップロードする画像ファイル配列"
 *             ),
 *             @OA\Property(
 *                 property="directory",
 *                 type="string",
 *                 description="アップロード先ディレクトリ",
 *                 example="recipes/steps"
 *             )
 *         )
 *     )
 * )
 * @OA\RequestBody(
 *     request="ImageDeleteBulkRequest",
 *     required=true,
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(
 *             required={"image_ids"},
 *             @OA\Property(property="image_ids", type="array", @OA\Items(type="string")),
 *         )
 *     )
 * )
 */

class ImageRequests {}
