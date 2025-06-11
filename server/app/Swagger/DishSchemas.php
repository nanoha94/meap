<?php

namespace App\Swagger;

/**
 * @OA\Schema(
 *     schema="Dish",
 *     required={"name"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="name", type="string", description="料理名", example="ハンバーグ"),
 *     @OA\Property(property="categories", type="array", description="カテゴリ",
 *         @OA\Items(ref="#/components/schemas/DishCategory")
 *     ),
 *     @OA\Property(property="ingredients", type="array", description="食材", 
 *         @OA\Items(ref="#/components/schemas/Ingredient")
 *     ),
 *     @OA\Property(property="seasonings", type="array", description="調味料", 
 *         @OA\Items(ref="#/components/schemas/Seasoning")
 *     ),
 *     @OA\Property(property="thumbnailUrl", type="string", description="サムネイルURL", example="https://www.google.com"),
 *     @OA\Property(property="url", type="string", description="レシピURL", example="https://www.google.com"),
 *     @OA\Property(property="recipe", type="string", description="レシピ", example="ハンバーグを作る"),
 *     @OA\Property(property="memo", type="string", description="メモ", example="ハンバーグは美味しい"),
 * )
 * 
 * @OA\Schema(
 *     schema="DishCategory",
 *     required={"id", "name"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="name", type="string", description="カテゴリ名", example="肉料理"),
 * )
 * 
 * @OA\Schema(
 *     schema="Seasoning",
 *     required={"id", "name", "quantity", "unit"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="name", type="string", description="調味料名", example="塩"),
 *     @OA\Property(property="quantity", type="double", description="量", example="1"),
 *     @OA\Property(property="unitId", type="string", description="単位", example="1")
 * )
 * 
 * @OA\Schema(
 *     schema="Ingredient",
 *     required={"id", "name", "quantity", "unit"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="name", type="string", description="食材名", example="牛肉"),
 *     @OA\Property(property="quantity", type="double", description="量", example="1"),
 *     @OA\Property(property="unitId", type="string", description="単位", example="1")
 * )
 */


class DishSchemas {}
