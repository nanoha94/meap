<?php

namespace App\Swagger;

/**
 * @OA\Schema(
 *     schema="Recipe",
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="name", type="string", description="料理名", example="ハンバーグ"),
 *     @OA\Property(property="categories", type="array", description="カテゴリ",
 *         @OA\Items(ref="#/components/schemas/RecipeCategory")
 *     ),
 *     @OA\Property(property="ingredients", type="array", description="食材", 
 *         @OA\Items(ref="#/components/schemas/Ingredient")
 *     ),
 *     @OA\Property(property="thumbnail", ref="#/components/schemas/RecipeThumbnail", description="サムネイル画像情報"),
 *     @OA\Property(property="url", type="string", description="レシピURL", example="https://www.google.com"),
 *     @OA\Property(property="steps", type="array", description="手順", 
 *         @OA\Items(ref="#/components/schemas/RecipeStep")
 *     ),
 *     @OA\Property(property="memo", type="string", description="メモ", example="ハンバーグは美味しい")
 * )
 * 
 * @OA\Schema(
 *     schema="RecipeThumbnail",
 *     nullable=true,
 *     @OA\Property(property="url", type="string", description="サムネイル画像URL", example="https://example.com/image.jpg"),
 *     @OA\Property(property="width", type="integer", description="サムネイル画像幅", example=300),
 *     @OA\Property(property="height", type="integer", description="サムネイル画像高さ", example=200),
 * )
 * 
 * @OA\Schema(
 *     schema="RecipeStep",
 *     required={"id", "instruction", "order"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="instruction", type="string", description="手順", example="ハンバーグを作る"),
 *     @OA\Property(property="image", ref="#/components/schemas/RecipeStepImage", description="手順画像情報"),
 *     @OA\Property(property="order", type="integer", description="並び順", example=1)
 * )
 * 
 * @OA\Schema(
 *     schema="RecipeStepImage",
 *     nullable=true,
 *     @OA\Property(property="url", type="string", description="画像URL", example="https://example.com/step1.jpg"),
 *     @OA\Property(property="width", type="integer", description="画像幅", example=300),
 *     @OA\Property(property="height", type="integer", description="画像高さ", example=200)
 * )
 * 
 * @OA\Schema(
 *     schema="RecipeCategory",
 *     required={"id", "name"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="name", type="string", description="カテゴリ名", example="肉料理"),
 *     @OA\Property(property="order", type="integer", description="並び順", example=1)
 * )
 * 
 */


class RecipeSchemas {}
