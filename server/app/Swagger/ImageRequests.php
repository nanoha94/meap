<?php

namespace App\Swagger;

/**
 * @OA\RequestBody(
 *     request="ImageGroupBulkUploadRequest",
 *     required=true,
 *     @OA\MediaType(
 *         mediaType="multipart/form-data",
 *         @OA\Schema(
 *             required={"images[0]"},
 *             @OA\Property(
 *                 property="images[0]",
 *                 type="string",
 *                 format="binary",
 *                 description="1枚目の画像ファイル（必須）"
 *             ),
 *             @OA\Property(
 *                 property="images[1]",
 *                 type="string",
 *                 format="binary",
 *                 description="2枚目の画像ファイル（オプション）"
 *             ),
 *             @OA\Property(
 *                 property="images[2]",
 *                 type="string",
 *                 format="binary",
 *                 description="3枚目の画像ファイル（オプション）"
 *             ),
 *             @OA\Property(
 *                 property="images[3]",
 *                 type="string",
 *                 format="binary",
 *                 description="4枚目の画像ファイル（オプション）"
 *             ),
 *             @OA\Property(
 *                 property="images[4]",
 *                 type="string",
 *                 format="binary",
 *                 description="5枚目の画像ファイル（オプション）"
 *             )
 *         )
 *     )
 * )
 * @OA\RequestBody(
 *     request="ImageUserUploadRequest",
 *     required=true,
 *     @OA\MediaType(
 *         mediaType="multipart/form-data",
 *         @OA\Schema(
 *             required={"image"},
 *             @OA\Property(
 *                 property="image",
 *                 type="string",
 *                 format="binary",
 *                 description="画像ファイル（必須）"
 *             )
 *         )
 *     )
 * )
 */
class ImageRequests {}
