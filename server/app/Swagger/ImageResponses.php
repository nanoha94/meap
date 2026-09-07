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
 * ユーザー画像アップロードレスポンス（success, message, data: Image）
 *
 * @OA\Schema(
 *     schema="UploadImageSingleResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="画像をアップロードしました。"),
 *     @OA\Property(property="data", ref="#/components/schemas/Image", description="アップロードされた画像")
 * )
 *
 * @OA\Response(
 *     response="ImageUploadBulkSuccess",
 *     description="複数画像アップロード成功",
 *     @OA\JsonContent(ref="#/components/schemas/UploadImageResponse")
 * )
 * @OA\Response(
 *     response="ImageUploadSuccess",
 *     description="画像アップロード成功",
 *     @OA\JsonContent(ref="#/components/schemas/UploadImageSingleResponse")
 * )
 */
class ImageResponses {}
