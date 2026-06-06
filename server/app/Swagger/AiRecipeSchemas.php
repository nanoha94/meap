<?php

namespace App\Swagger;

/**
 * @OA\Schema(
 *     schema="ParsedRecipeIngredient",
 *     required={"name", "unitName", "categoryName"},
 *     @OA\Property(property="name", type="string", description="材料名", example="玉ねぎ"),
 *     @OA\Property(property="quantity", type="number", nullable=true, description="数量", example=1),
 *     @OA\Property(property="unitName", type="string", description="単位名", example="個"),
 *     @OA\Property(property="categoryName", type="string", description="材料カテゴリ名", example="野菜")
 * )
 *
 * @OA\Schema(
 *     schema="ParsedRecipeStep",
 *     required={"instruction"},
 *     @OA\Property(property="instruction", type="string", description="調理手順", example="玉ねぎをみじん切りにする")
 * )
 *
 * @OA\Schema(
 *     schema="ParsedRecipe",
 *     required={"name", "url", "ingredients", "steps"},
 *     @OA\Property(property="name", type="string", description="料理名", example="カレーライス"),
 *     @OA\Property(property="servingCount", type="integer", nullable=true, description="人数", example=4),
 *     @OA\Property(property="url", type="string", description="URL", example=""),
 *     @OA\Property(
 *         property="ingredients",
 *         type="array",
 *         description="材料一覧",
 *         @OA\Items(ref="#/components/schemas/ParsedRecipeIngredient")
 *     ),
 *     @OA\Property(
 *         property="steps",
 *         type="array",
 *         description="手順一覧",
 *         @OA\Items(ref="#/components/schemas/ParsedRecipeStep")
 *     )
 * )
 */
class AiRecipeSchemas {}
