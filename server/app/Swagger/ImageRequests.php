<?php

namespace App\Swagger;

/**
 * @OA\RequestBody(
 *     request="ImageBulkUploadRequest",
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
 *     request="ImageBulkDestroyRequest",
 *     required=true,
 *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(
 *             required={"ids"},
 *             @OA\Property(property="ids", type="array", @OA\Items(type="string")),
 *         )
 *     )
 * )
 */

class ImageRequests {}
