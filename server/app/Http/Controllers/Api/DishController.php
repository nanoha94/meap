<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DishController extends Controller
{
    /**
     * @OA\Get(
     *     path="/dishes",
     *     summary="料理一覧を取得",
     *     description="料理一覧を返します。",
     *     tags={"Dishes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/DishPageParam"),
     *     @OA\Parameter(ref="#/components/parameters/DishPerPageParam"),
     *     @OA\Response(response=200, ref="#/components/responses/DishIndexSuccess"),
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
     *     path="/dishes",
     *     summary="料理を作成",
     *     description="新しい料理を作成します。",
     *     tags={"Dishes"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/DishRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/DishStoreSuccess"),
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
     *     path="/dishes/{id}",
     *     summary="料理の詳細を取得",
     *     description="指定された料理の詳細を返します。",
     *     tags={"Dishes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/DishIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/DishShowSuccess"),
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
     *     path="/dishes/{id}",
     *     summary="料理を更新",
     *     description="指定された料理を更新します。",
     *     tags={"Dishes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/DishIdParam"),
     *     @OA\RequestBody(ref="#/components/requestBodies/DishRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/DishUpdateSuccess"),
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
     *     path="/dishes/{id}",
     *     summary="料理を削除",
     *     description="指定された料理を削除します。",
     *     tags={"Dishes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/DishIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/DishDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(string $id)
    {
        //
    }
}
