<?php

namespace App\Swagger;

/**
 * 買い物アイテム一覧取得レスポンス（BaseApiIndexResponse + data: ShoppingItem[]）
 *
 * @OA\Schema(
 *     schema="ShoppingItemIndexResponse",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/BaseApiIndexResponse"),
 *         @OA\Schema(
 *             required={"data"},
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 description="買い物アイテム一覧（カテゴリーのorder順でソートされた1次元配列）",
 *                 @OA\Items(ref="#/components/schemas/ShoppingItem")
 *             )
 *         )
 *     }
 * )
 *
 * @OA\Response(
 *     response="ShoppingItemIndexSuccess",
 *     description="買い物リストを5件取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/ShoppingItemIndexResponse")
 * )
 * @OA\Response(
 *     response="ShoppingItemBulkStoreSuccess",
 *     description="買い物アイテムを3件作成しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * @OA\Response(
 *     response="ShoppingItemBulkUpdateSuccess",
 *     description="買い物アイテムを3件更新しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * @OA\Response(
 *     response="ShoppingItemBulkDestroySuccess",
 *     description="買い物アイテムを2件削除しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * 
 * 買い物カテゴリー一覧取得レスポンス（BaseApiIndexResponse + data: ShoppingCategory[]）
 *
 * @OA\Schema(
 *     schema="ShoppingCategoryIndexResponse",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/BaseApiIndexResponse"),
 *         @OA\Schema(
 *             required={"data"},
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 description="買い物カテゴリ一覧",
 *                 @OA\Items(ref="#/components/schemas/ShoppingCategory")
 *             )
 *         )
 *     }
 * )
 *
 * @OA\Response(
 *     response="ShoppingCategoryIndexSuccess",
 *     description="買い物カテゴリーを5件取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/ShoppingCategoryIndexResponse")
 * )
 * 買い物カテゴリー詳細取得レスポンス（success, message, data: ShoppingCategory）
 *
 * @OA\Schema(
 *     schema="ShoppingCategoryShowResponse",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="買い物カテゴリー(食品)を取得しました。"),
 *     @OA\Property(property="data", ref="#/components/schemas/ShoppingCategory")
 * )
 *
 * @OA\Response(
 *     response="ShoppingCategoryShowSuccess",
 *     description="買い物カテゴリー(食品)を取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/ShoppingCategoryShowResponse")
 * )
 * @OA\Response(
 *     response="ShoppingCategoryStoreSuccess",
 *     description="買い物カテゴリー(食品)を作成しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * @OA\Response(
 *     response="ShoppingCategoryBulkStoreSuccess",
 *     description="買い物カテゴリーを3件作成しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * @OA\Response(
 *     response="ShoppingCategoryBulkUpdateSuccess",
 *     description="買い物カテゴリーを3件更新しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * @OA\Response(
 *     response="ShoppingCategoryBulkDestroySuccess",
 *     description="買い物カテゴリーを2件削除しました。",
 *     @OA\JsonContent(ref="#/components/schemas/BaseApiResponse")
 * )
 * 買い物タグ一覧取得レスポンス（BaseApiIndexResponse + data: ShoppingTag[]）
 *
 * @OA\Schema(
 *     schema="ShoppingTagIndexResponse",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/BaseApiIndexResponse"),
 *         @OA\Schema(
 *             required={"data"},
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 description="買い物タグ一覧",
 *                 @OA\Items(ref="#/components/schemas/ShoppingTag")
 *             )
 *         )
 *     }
 * )
 *
 * @OA\Response(
 *     response="ShoppingTagIndexSuccess",
 *     description="買い物タグを5件取得しました。",
 *     @OA\JsonContent(ref="#/components/schemas/ShoppingTagIndexResponse")
 * )
 * 
 */

class ShoppingResponse {}
