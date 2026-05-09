<?php

namespace App\Swagger;

/**
 * @OA\Schema(
 *     schema="IngredientItem",
 *     required={"id", "name"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="name", type="string", description="食材名", example="牛肉"),
 *     @OA\Property(property="quantity", type="number", nullable=true, description="量", example=1.5),
 *     @OA\Property(property="unit", ref="#/components/schemas/IngredientUnit", description="単位情報"),
 *     @OA\Property(property="categoryId", type="string", description="カテゴリID", example="1"),
 *     @OA\Property(property="order", type="integer", description="並び順", example=1)
 * )
 * 
 * @OA\Schema(
 *     schema="IngredientCategory",
 *     required={"id", "name"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="name", type="string", description="カテゴリ名", example="食材"),
 *     @OA\Property(property="isDefault", type="boolean", description="デフォルトカテゴリかどうか", example=false),
 *     @OA\Property(property="order", type="integer", description="順番", example=1)
 * )
 * 
 * @OA\Schema(
 *     schema="IngredientUnit",
 *     required={"id", "name", "requiresQuantity", "order"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="name", type="string", description="食材単位名", example="g"),
 *     @OA\Property(property="position", type="string", description="単位の位置（prefix: 数値の前、suffix: 数値の後）", example="prefix"),
 *     @OA\Property(property="requiresQuantity", type="boolean", description="量を入力するかどうか", example=true),
 *     @OA\Property(property="order", type="integer", description="並び順", example=1)
 * )
 */


class IngredientSchemas {}
