<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="Unauthorized",
 *     description="認証エラー",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=false),
 *         @OA\Property(
 *             property="message",
 *             type="string",
 *             example="認証が必要です。"
 *         )
 *     )
 * )
 * 
 * @OA\Response(
 *     response="NotFound",
 *     description="リソースが見つかりませんでした",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=false),
 *         @OA\Property(
 *             property="message",
 *             type="string",
 *             example="指定されたレコードが見つかりません。"
 *         )
 *     )
 * )
 * 
 * @OA\Response(
 *     response="ValidationErrors",
 *     description="バリデーションエラー",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=false),
 *         @OA\Property(
 *             property="message",
 *             type="string",
 *             example="入力内容に誤りがあります。"
 *         ),
 *         @OA\Property(
 *             property="errors",
 *             type="object",
 *             @OA\Property(
 *                 property="name",
 *                 type="array",
 *                 @OA\Items(type="string", example="名前は必須です。")
 *             )
 *         )
 *     )
 * )
 * 
 * @OA\Response(
 *     response="UnexpectedError",
 *     description="予期せぬエラー",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=false),
 *         @OA\Property(
 *             property="message",
 *             type="string",
 *             example="エラーが発生しました。"
 *         )
 *     )
 * )
 */



class CommonResponses {}
