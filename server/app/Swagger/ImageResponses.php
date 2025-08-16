<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="ImageUploadBulkSuccess",
 *     description="複数画像アップロード成功",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="5枚の画像をアップロードしました。"),
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="string", example="1"),
 *                 @OA\Property(property="src", type="string", example="/storage/group_id/recipes/steps/filename.jpg"),
 *                 @OA\Property(property="width", type="integer", example=800),
 *                 @OA\Property(property="height", type="integer", example=600),
 *             )
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="ImageDeleteBulkSuccess",
 *     description="画像一括削除成功",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="3件の画像を削除しました。"),
 *         @OA\Property(property="data", type="null", example=null)
 *     )
 * )
 */

class ImageResponses {}
