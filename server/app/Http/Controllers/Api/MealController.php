<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MealController extends Controller
{
    /**
     * @OA\Get(
     *     path="/meals",
     *     summary="献立一覧を取得",   
     *     tags={"Meals"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/MealIndexSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index()
    {
        //
    }

    /**
     * @OA\Post(
     *     path="/meals",
     *     summary="献立を作成",
     *     description="献立を作成します。",
     *     tags={"Meals"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/MealRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/MealStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * @OA\Get(
     *     path="/meals/{id}",
     *     summary="献立の詳細を取得",
     *     tags={"Meals"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/MealIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/MealShowSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(string $id)
    {
        //
    }

    /**
     * @OA\Put(
     *     path="/meals/{id}",
     *     summary="献立を更新",
     *     tags={"Meals"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/MealIdParam"),
     *     @OA\RequestBody(ref="#/components/requestBodies/MealRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/MealUpdateSuccess"),
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
     *     path="/meals/{id}",
     *     summary="献立を削除",
     *     tags={"Meals"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/MealIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/MealDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(string $id)
    {
        //
    }
}
