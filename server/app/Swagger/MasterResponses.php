<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="MasterSuccess",
 *     description="正常に取得されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="dishCategories",
 *             type="array",
 *             description="料理カテゴリ一覧",
 *             @OA\Items(ref="#/components/schemas/DishCategory")
 *         ),
 * 　　　　@OA\Property(
 *             property="shoppingCategories",
 *             type="array",
 *             description="買い物カテゴリ一覧",
 *             @OA\Items(ref="#/components/schemas/ShoppingCategory")
 *         ),
 *         @OA\Property(
 *             property="shoppingTags",
 *             type="array",
 *             description="買い物タグ一覧",
 *             @OA\Items(ref="#/components/schemas/ShoppingTag")
 *         ),
 *     )
 * )
 */
class MasterResponses {}
