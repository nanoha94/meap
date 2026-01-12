<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="MealPlanIndexSuccess",
 *     description="献立を5件取得しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="献立を5件取得しました。"),
 *         @OA\Property(property="data", type="object",
 *             @OA\Property(property="date", type="string", format="date", description="日付", example="2023-10-05"),
 *             @OA\Property(property="mealPlans", type="array", description="献立メニュー",
 *                 @OA\Items(type="object",
 *                     @OA\Property(property="id", type="string", description="ID", example="1"),
 *                     @OA\Property(property="date", type="string", format="date", description="日付", example="2023-10-05"),
 *                     @OA\Property(property="category", type="object",
 *                         @OA\Property(property="id", type="string", description="ID", example="1"),
 *                         @OA\Property(property="name", type="string", description="カテゴリ名", example="朝食"),
 *                         @OA\Property(property="colorId", type="string", description="色ID", example="1")
 *                     ),
 *                     @OA\Property(property="menu", type="array", description="献立",
 *                         @OA\Items(type="object",
 *                             @OA\Property(property="category", type="object",
 *                                 @OA\Property(property="id", type="string", description="ID", example="1"),
 *                                 @OA\Property(property="name", type="string", description="カテゴリ名", example="朝食"),
 *                             ),
 *                             @OA\Property(property="recipes", type="array", description="料理",
 *                                 @OA\Items(type="object",
 *                                     @OA\Property(property="id", type="string", description="ID", example="1"),
 *                                     @OA\Property(property="name", type="string", description="料理名", example="ハンバーグ"),
 *                                     @OA\Property(property="categories", type="array", description="カテゴリ",
 *                                         @OA\Items(ref="#/components/schemas/RecipeCategory")
 *                                     ),
 *                                     @OA\Property(property="thumbnail", type="object", ref="#/components/schemas/RecipeThumbnail"),
 *                                 ),
 *                             )
 *                         )
 *                     )
 *                 )
 *             )
 *         ),
 *         @OA\Property(
 *             property="total",
 *             type="integer",
 *             description="献立総数",
 *             example=5
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="MealPlanStoreSuccess",
 *     description="献立(2024-01-15)を作成しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="献立(2024-01-15)を作成しました。"),
 *         @OA\Property(property="data", ref="#/components/schemas/MealPlan")
 *     )
 * )
 * @OA\Response(
 *     response="MealPlanShowSuccess",
 *     description="献立(2024-01-15)を取得しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="献立(2024-01-15)を取得しました。"),
 *         @OA\Property(property="data", ref="#/components/schemas/MealPlan")
 *     )
 * )
 * @OA\Response(
 *     response="MealPlanUpdateSuccess",
 *     description="献立(2024-01-15)を更新しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="献立(2024-01-15)を更新しました。"),
 *         @OA\Property(property="data", ref="#/components/schemas/MealPlan")
 *     )
 * )
 * @OA\Response(
 *     response="MealPlanDestroySuccess",
 *     description="献立(2024-01-15)を削除しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="献立(2024-01-15)を削除しました。"),
 *         @OA\Property(property="data", type="null", example=null)
 *     )
 * )
 * 
 * @OA\Response(
 *     response="MealCategoryIndexSuccess",
 *     description="献立カテゴリ一覧を取得しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="献立カテゴリ一覧を取得しました。"),
 *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/MealCategory")),
 *         @OA\Property(property="total", type="integer", example=100)
 *     )
 * )
 * @OA\Response(
 *     response="MealCategoryStoreSuccess",
 *     description="献立カテゴリ(朝食)を作成しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="献立カテゴリ(朝食)を作成しました。"),
 *         @OA\Property(
 *             property="data",
 *             type="object",
 *             @OA\Property(property="id", type="string", example="1"),
 *             @OA\Property(property="name", type="string", example="朝食"),
 *             @OA\Property(property="colorId", type="string", example="2"),
 *             @OA\Property(property="order", type="integer", example=0)
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="MealCategoryBulkUpdateSuccess",
 *     description="3件の献立カテゴリを更新しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="3件の献立カテゴリを更新しました。"),
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/MealCategory")
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="MealCategoryDestroySuccess",
 *     description="献立カテゴリ(昼食)を削除しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="献立カテゴリ(昼食)を削除しました。"),
 *         @OA\Property(property="data", type="null", example=null)
 *     )
 * )
 * 
 *  * @OA\Response(
 *     response="MenuCategoryIndexSuccess",
 *     description="正常に取得されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             description="コース種別一覧",
 *             @OA\Items(ref="#/components/schemas/MenuCategory")
 *         ),
 *         @OA\Property(
 *             property="total",
 *             type="integer",
 *             description="コース種別総数",
 *             example=100
 *         )
 *     )
 * )
 */
class MealPlanResponses {}
