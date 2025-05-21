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
     *     description="献立一覧を返します。",
     *     tags={"Meals"}, 
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="成功",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", description="ID"),
     *                 @OA\Property(property="date", type="string", description="日付"),
     *                 @OA\Property(property="mealSets", type="array", description="同じ日の献立リスト", 
     *                     @OA\Items(type="object", 
     *                         @OA\Property(property="categoryId", type="string", description="種別ID"),
     *                         @OA\Property(property="dishes", type="array", description="料理一覧", 
     *                             @OA\Items(type="object", 
     *                                 @OA\Property(property="id", type="string", description="ID"),
     *                                 @OA\Property(property="roleId", type="string", description="区分"),
     *                                 @OA\Property(property="name", type="string", description="名前"),
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
