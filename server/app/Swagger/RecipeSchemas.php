<?php

namespace App\Swagger;

/**
 * @OA\Schema(
 *     schema="Recipe",
 *     @OA\Property(property="id", type="string", description="ID", example="1"),
 *     @OA\Property(property="name", type="string", description="料理名", example="ハンバーグ"),
 *     @OA\Property(property="categories", type="array", description="料理カテゴリ",
 *         @OA\Items(ref="#/components/schemas/RecipeCategory")
 *     ),
 *     @OA\Property(property="ingredientCategories", type="array", description="食材カテゴリ",
 *         @OA\Items(ref="#/components/schemas/IngredientCategory")
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
 *     @OA\Property(property="categories", type="array", description="料理カテゴリ",
 *         @OA\Items(ref="#/components/schemas/RecipeCategory")
 *     ),
 *     @OA\Property(property="thumbnail", ref="#/components/schemas/Image", nullable=true, description="サムネイル画像情報"),
 *     @OA\Property(property="lastPlannedDate", type="string", format="date", nullable=true, description="前回の献立日"),
 *     @OA\Property(property="cookingTime", type="integer", nullable=true, description="調理時間（分）")
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
 * POST/PUT レシピリクエスト用。レスポンスの ingredientCategories は IngredientCategory[]。
 * isDefault は POST 時のみ指定。ingredientCategories 指定時は isDefault: true をちょうど1件含めること。
 *
 * @OA\Schema(
 *     schema="RecipeIngredientCategory",
 *     required={"order"},
 *     @OA\Property(property="id", type="string", nullable=true, description="食材カテゴリID（既存カテゴリ参照時。指定時は name は省略可・無視される）", example="550e8400-e29b-41d4-a716-446655440001"),
 *     @OA\Property(property="name", type="string", nullable=true, description="食材カテゴリ名（id 未指定時は必須。既存検索 or 新規作成）", example="調味料"),
 *     @OA\Property(property="isDefault", type="boolean", nullable=true, description="デフォルトカテゴリかどうか（POST 時のみ指定。ingredientCategories 指定時は true を1件のみ）", example=false),
 *     @OA\Property(property="order", type="integer", description="並び順（0以上）", example=1, minimum=0)
 * )
 *
 * POST/PUT レシピリクエスト用。レスポンスの ingredients は IngredientItem[]。
 *
 * @OA\Schema(
 *     schema="RecipeIngredient",
 *     @OA\Property(property="id", type="string", nullable=true, description="ID（既存食材参照時。指定時は name は省略可・無視される）", example="1"),
 *     @OA\Property(property="name", type="string", nullable=true, description="食材名（id 未指定時は必須。既存検索 or 新規作成）", example="牛肉"),
 *     @OA\Property(
 *         property="quantityDisplay",
 *         type="string",
 *         nullable=true,
 *         maxLength=50,
 *         description="数量の表示表記。分数、小数、整数が指定可能。例: 1/2, 0.5, 200",
 *         example="1/2"
 *     ),
 *     @OA\Property(property="unitId", type="string", description="単位ID", example="1"),
 *     @OA\Property(property="categoryId", type="string", nullable=true, description="カテゴリID（既存カテゴリへの紐づけ。categoryName と両方指定時は categoryId 優先）", example="1"),
 *     @OA\Property(property="categoryName", type="string", nullable=true, description="カテゴリ名（新規カテゴリへの紐づけ。ingredientCategories.*.name と一致する値を指定。両方省略時は isDefault カテゴリへフォールバック）", example="調味料"),
 *     @OA\Property(property="order", type="integer", nullable=true, description="並び順", example=1)
 * )
 *
 * POST/PUT レシピリクエスト用。レスポンスの steps は RecipeStep[]。
 *
 * @OA\Schema(
 *     schema="RecipeStepItem",
 *     @OA\Property(property="id", type="string", nullable=true, description="手順ID", example="1"),
 *     @OA\Property(property="instruction", type="string", description="手順", example="ハンバーグを作る"),
 *     @OA\Property(property="imageId", type="string", nullable=true, description="画像ID", example="1"),
 *     @OA\Property(property="order", type="integer", description="並び順", example=1)
 * )
 * 
 */


class RecipeSchemas {}
