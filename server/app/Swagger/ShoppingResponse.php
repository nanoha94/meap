<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="ShoppingItemIndexSuccess",
 *     description="買い物リストを5件取得しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="買い物リストを5件取得しました。"),
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(
 *                     property="category",
 *                     ref="#/components/schemas/ShoppingCategory",
 *                     description="カテゴリ情報"
 *                 ),
 *                 @OA\Property(
 *                     property="items",
 *                     type="array",
 *                     @OA\Items(ref="#/components/schemas/ShoppingItem")
 *                 )
 *             )
 *         ),
 *         @OA\Property(property="total", type="integer", example=5)
 *     )
 * )
 * @OA\Response(
 *     response="ShoppingItemStoreSuccess",
 *     description="買い物アイテム(りんご)を作成しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="買い物アイテム(りんご)を作成しました。"),
 *         @OA\Property(property="data", ref="#/components/schemas/ShoppingItem")
 *     )
 * )
 * @OA\Response(
 *     response="ShoppingItemShowSuccess",
 *     description="買い物アイテム(りんご)を取得しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="買い物アイテム(りんご)を取得しました。"),
 *         @OA\Property(property="data", ref="#/components/schemas/ShoppingItem")
 *     )
 * )
 * @OA\Response(
 *     response="ShoppingItemBulkUpdateSuccess",
 *     description="買い物アイテムを3件更新しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="買い物アイテムを3件更新しました。"),
 *         @OA\Property(property="data", ref="#/components/schemas/ShoppingItem")
 *     )
 * )
 * @OA\Response(
 *     response="ShoppingItemBulkDestroySuccess",
 *     description="買い物アイテムを2件削除しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="買い物アイテムを2件削除しました。"),
 *         @OA\Property(property="data", type="null", example=null)
 *     )
 * )
 * 
 * @OA\Response(
 *     response="ShoppingCategoryIndexSuccess",
 *     description="買い物カテゴリーを5件取得しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="買い物カテゴリーを5件取得しました。"),
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             description="買い物カテゴリ一覧",
 *             @OA\Items(ref="#/components/schemas/ShoppingCategory")
 *         ),
 *         @OA\Property(
 *             property="total",
 *             type="integer",
 *             description="買い物カテゴリ総数",
 *             example=5
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="ShoppingCategoryStoreSuccess",
 *     description="買い物カテゴリー(食品)を作成しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="買い物カテゴリー(食品)を作成しました。"),
 *         @OA\Property(property="data", ref="#/components/schemas/ShoppingCategory")
 *     )
 * )
 * @OA\Response(
 *     response="ShoppingCategoryBulkUpdateSuccess",
 *     description="買い物カテゴリーを3件更新しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="買い物カテゴリーを3件更新しました。"),
 *         @OA\Property(property="data", ref="#/components/schemas/ShoppingCategory")
 *     )
 * )
 * @OA\Response(
 *     response="ShoppingCategoryBulkDestroySuccess",
 *     description="買い物カテゴリーを2件削除しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="買い物カテゴリーを2件削除しました。"),
 *         @OA\Property(property="data", type="null", example=null)
 *     )
 * )
 * 
 * @OA\Response(
 *     response="ShoppingTagIndexSuccess",
 *     description="買い物タグを5件取得しました。",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="買い物タグを5件取得しました。"),
 *         @OA\Property(
 *             property="data",
 *             type="object",
 *             @OA\Property(property="tags", type="array", @OA\Items(ref="#/components/schemas/ShoppingTag")),
 *             @OA\Property(property="total", type="integer", example=5)
 *         ),
 *         @OA\Property(property="total", type="integer", example=5)
 *     )
 * )
 * 
 */

class ShoppingResponse {}
