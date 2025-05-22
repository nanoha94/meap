<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DishCategoryController extends Controller
{
    /**
     * @OA\Post(
     *     path="/dishes/categories",
     *     summary="料理カテゴリを作成",
     *     description="新しい料理カテゴリを作成します。",
     *     tags={"Dishes"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/DishCategoryRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/DishCategoryStoreSuccess"),
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
     *     path="/dishes/categories/{id}",
     *     summary="料理カテゴリを更新",
     *     description="指定された料理カテゴリを更新します。",
     *     tags={"Dishes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/DishCategoryIdParam"),
     *     @OA\RequestBody(ref="#/components/requestBodies/DishCategoryRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/DishCategoryUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * @OA\Delete(
     *     path="/dishes/categories/{id}",
     *     summary="料理カテゴリを削除",
     *     description="指定された料理カテゴリを削除します。",
     *     tags={"Dishes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/DishCategoryIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/DishCategoryDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(string $id)
    {
        //
    }
}
