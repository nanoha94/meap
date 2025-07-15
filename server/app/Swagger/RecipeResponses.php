<?php

namespace App\Swagger;

/**
 * @OA\Response(
 *     response="RecipeIndexSuccess",
 *     description="正常に取得されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="recipes",
 *             type="array",
 *             description="料理データ一覧",
 *             @OA\Items(ref="#/components/schemas/Recipe")
 *         ),
 *         @OA\Property(
 *             property="total",
 *             type="integer",
 *             description="料理総数",
 *             example=100
 *         )
 *     )
 * )
 * @OA\Response(
 *     response="RecipeStoreSuccess",
 *     description="正常に登録されました",
 *     @OA\JsonContent(ref="#/components/schemas/Recipe")
 * )
 * @OA\Response(
 *     response="RecipeShowSuccess",
 *     description="正常に取得されました",
 *     @OA\JsonContent(ref="#/components/schemas/Recipe")
 * )
 * @OA\Response(
 *     response="RecipeUpdateSuccess",
 *     description="正常に更新されました",
 *     @OA\JsonContent(ref="#/components/schemas/Recipe")
 * )
 * @OA\Response(
 *     response="RecipeDestroySuccess",
 *     description="正常に削除されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="id", type="string")
 *     )
 * )
 * @OA\Response(
 *     response="RecipeCategoryStoreSuccess",
 *     description="正常に登録されました",
 *     @OA\JsonContent(ref="#/components/schemas/RecipeCategory")
 * )
 * @OA\Response(
 *     response="RecipeCategoryUpdateSuccess",
 *     description="正常に更新されました",
 *      @OA\JsonContent(ref="#/components/schemas/RecipeCategory")
 * )
 * @OA\Response(
 *     response="RecipeCategoryDestroySuccess",
 *     description="正常に削除されました",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="id", type="string")
 *     )
 * )
 */
class RecipeResponses {}
