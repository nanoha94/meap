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
 *         @OA\Items(ref="#/components/schemas/IngredientItem")
 *     ),
 *     @OA\Property(property="thumbnail", ref="#/components/schemas/Image", nullable=true, description="サムネイル画像情報"),
 *     @OA\Property(property="url", type="string", description="レシピURL", example="https://www.google.com"),
 *     @OA\Property(property="steps", type="array", description="手順", 
 *         @OA\Items(ref="#/components/schemas/RecipeStep")
 *     ),
 *     @OA\Property(property="memo", type="string", description="メモ", example="ハンバーグは美味しい"),
 *     @OA\Property(property="servingCount", type="integer", nullable=true, description="分量（○○人分）", example=4),
 *     @OA\Property(property="ownerUserId", type="string", description="作成者のユーザーID", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="status", type="string", enum={"limited", "public"}, description="公開状態", example="limited"),
 *     @OA\Property(property="publishedRecipeId", type="string", nullable=true, description="公開レシピID（セカンドリリースで使用）", example=null)
 * )
 *
 * レシピ一覧用（id, name, categories, thumbnail のみ）
 *
 * @OA\Schema(
 *     schema="RecipeListItem",
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="name", type="string", description="料理名", example="ハンバーグ"),
 *     @OA\Property(property="categories", type="array", description="カテゴリ",
 *         @OA\Items(ref="#/components/schemas/RecipeCategory")
 *     ),
 *     @OA\Property(property="thumbnail", ref="#/components/schemas/Image", nullable=true, description="サムネイル画像情報")
 * )
 *
 * @OA\Schema(
 *     schema="RecipeStep",
 *     required={"id", "instruction", "order"},
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="instruction", type="string", description="手順", example="ハンバーグを作る"),
 *     @OA\Property(property="image", ref="#/components/schemas/Image", nullable=true, description="手順画像情報"),
 *     @OA\Property(property="order", type="integer", description="並び順", example=1)
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
