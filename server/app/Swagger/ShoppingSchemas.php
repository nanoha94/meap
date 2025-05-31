<?php

namespace App\Swagger;

/**
 * @OA\Schema(
 *     schema="ShoppingItem",
 *     required={"id", "name", "isPinned", "isChecked", "categoryId", "order"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="name", type="string", description="買い物アイテム名（数量込み）", example="ひき肉100g"),
 *     @OA\Property(property="isPinned", type="boolean", description="ピン留め", example="false"),
 *     @OA\Property(property="isChecked", type="boolean", description="チェック状態", example="false"),
 *     @OA\Property(property="categoryId", type="string", description="カテゴリ情報"),
 *     @OA\Property(property="order", type="integer", description="順番", example=1),
 * )
 * 
 * @OA\Schema(
 *     schema="ShoppingCategory",
 *     required={"id", "name"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="name", type="string", description="カテゴリ名", example="スーパーA"),
 *     @OA\Property(property="isDefault", type="boolean", description="デフォルト"),
 *     @OA\Property(property="order", type="integer", description="順番")
 * )
 * 
 * @OA\Schema(
 *     schema="ShoppingTag",
 *     required={"id", "name"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="name", type="string", description="タグ名", example="サラダ"),
 * )
 */


class ShoppingSchemas {}
