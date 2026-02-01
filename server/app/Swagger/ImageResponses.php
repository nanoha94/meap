<?php

namespace App\Swagger;

/**
 * 画像アップロードレスポンス（BaseApiIndexResponse + data: Image[]）
 *
 * @OA\Schema(
 *     schema="UploadImageResponse",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/BaseApiIndexResponse"),
 *         @OA\Schema(
 *             required={"data"},
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 description="アップロードされた画像一覧",
 *                 @OA\Items(ref="#/components/schemas/Image")
 *             )
 *         )
 *     }
 * )
 *
 * @OA\Response(
 *     response="ImageUploadBulkSuccess",
 *     description="複数画像アップロード成功",
 *     @OA\JsonContent(ref="#/components/schemas/UploadImageResponse")
 * )
 * @OA\Response(
 *     response="ImageDeleteBulkSuccess",
 *     description="画像一括削除成功",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 */

class ImageResponses {}
