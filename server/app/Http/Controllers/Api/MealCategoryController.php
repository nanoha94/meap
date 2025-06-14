<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MealCategoryController extends Controller
{
    /**
     * @OA\Post(
     *     path="/meals/categories",
     *     summary="献立カテゴリを作成",
     *     tags={"Meals"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/MealCategoryRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/MealCategoryStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * @OA\Put(
     *     path="/meals/categories/bulk",
     *     summary="献立カテゴリを更新",
     *     tags={"Meals"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/MealCategoryRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/MealCategoryBulkUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function bulkUpdate(Request $request)
    {
        //
    }

    /**
     * @OA\Delete(
     *     path="/meals/categories/bulk",
     *     summary="献立カテゴリを削除",
     *     tags={"Meals"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/MealCategoryBulkDestroyRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/MealCategoryBulkDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function bulkDestroy(Request $request)
    {
        //
    }
}
