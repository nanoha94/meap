<?php

namespace App\Swagger;

/**
 * 献立一覧取得レスポンス（BaseApiIndexResponse + data: 日付＋献立メニュー）
 *
 * @OA\Schema(
 *     schema="MealPlanIndexResponse",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/BaseApiIndexResponse"),
 *         @OA\Schema(
 *             required={"data"},
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 description="日付と献立メニュー",
 *                 @OA\Items( ref="#/components/schemas/MealPlan")
 *             )
 *         )
 *     }
 * )
 *
 * @OA\Response(
 *     response="MealPlanIndexSuccess",
 *     description="献立を5件取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/MealPlanIndexResponse")
 * )
 *
 * 献立詳細取得レスポンス（success, message, data: MealPlan）
 *
 * @OA\Schema(
 *     schema="MealPlanShowResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="献立(2024-01-15)を取得しました。"),
 *     @OA\Property(property="data", ref="#/components/schemas/MealPlan")
 * )
 *
 * @OA\Response(
 *     response="MealPlanStoreSuccess",
 *     description="献立(2024-01-15)を作成しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * @OA\Response(
 *     response="MealPlanShowSuccess",
 *     description="献立(2024-01-15)を取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/MealPlanShowResponse")
 * )
 * @OA\Response(
 *     response="MealPlanUpdateSuccess",
 *     description="献立(2024-01-15)を更新しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * @OA\Response(
 *     response="MealPlanDestroySuccess",
 *     description="献立(2024-01-15)を削除しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * @OA\Response(
 *     response="MealPlanMealDestroySuccess",
 *     description="献立の1食を削除しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 *
 * 献立カテゴリ一覧取得レスポンス（BaseApiIndexResponse + data: MealCategory[]）
 *
 * @OA\Schema(
 *     schema="MealCategoryIndexResponse",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/BaseApiIndexResponse"),
 *         @OA\Schema(
 *             required={"data"},
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 description="献立カテゴリデータ一覧",
 *                 @OA\Items(ref="#/components/schemas/MealCategory")
 *             )
 *         )
 *     }
 * )
 *
 * @OA\Response(
 *     response="MealCategoryIndexSuccess",
 *     description="献立カテゴリ―一覧を取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/MealCategoryIndexResponse")
 * )
 * @OA\Response(
 *     response="MealCategoryBulkStoreSuccess",
 *     description="献立カテゴリを3件作成しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * @OA\Response(
 *     response="MealCategoryBulkUpdateSuccess",
 *     description="3件の献立カテゴリを更新しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * @OA\Response(
 *     response="MealCategoryBulkDestroySuccess",
 *     description="献立カテゴリを2件削除しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 */
class MealPlanResponses {}
