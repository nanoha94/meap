<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Models\DishRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        // TODO: 月別に取得できるようにする
        $meals = $group->meals()
            ->select('id', 'date', 'meal_category_id')
            ->with(['mealCategory', 'dishes.dishRoles', 'dishes.categories', 'dishes.seasonings', 'dishes.ingredients'])
            ->get()
            ->groupBy('date')
            ->values();

        $res = [
            'meals' => $meals->map(function ($dateMeals, $date) {
                return [
                    'date' => $date,
                    'meals' => $dateMeals->map(function ($meal) {
                        return [
                            'id' => $meal->id,
                            'date' => $meal->date,
                            'category' => [
                                "id" => $meal->mealCategory->id,
                                "name" => $meal->mealCategory->name,
                                "colorId" => $meal->mealCategory->color_id,
                            ],
                            'menu' => $meal->dishes->groupBy('pivot.dish_role_id')->map(function ($dishes, $roleId) {
                                $role = DishRole::find($roleId);
                                return [
                                    'role' => [
                                        'id' => $role->id,
                                        'name' => $role->name
                                    ],
                                    'dishes' => $dishes->map(fn($dish) => [
                                        'id' => $dish->id,
                                        'name' => $dish->name,
                                        'thumbnailUrl' => $dish->thumbnail_url,
                                        'url' => $dish->url,
                                        'recipe' => $dish->recipe,
                                        'memo' => $dish->memo,
                                        'categories' => $dish->categories->map(fn($category) => [
                                            'id' => $category->id,
                                            'name' => $category->name
                                        ]),
                                        'seasonings' => $dish->seasonings->map(fn($seasoning) => [
                                            'id' => $seasoning->id,
                                            'name' => $seasoning->name,
                                            'quantity' => $seasoning->pivot->quantity,
                                            'unitId' => $seasoning->pivot->unit_id
                                        ]),
                                        'ingredients' => $dish->ingredients->map(fn($ingredient) => [
                                            'id' => $ingredient->id,
                                            'name' => $ingredient->name,
                                            'quantity' => $ingredient->pivot->quantity,
                                            'unitId' => $ingredient->pivot->unit_id
                                        ])
                                    ])
                                ];
                            })->values()
                        ];
                    })
                ];
            })->values()
        ];

        return response()->json($res, 200);
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
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $ret = Meal::create([
            'group_id' => $group->id,
            'meal_category_id' => $request->categoryId,
            'date' => $request->date,
        ]);

        // 料理を紐づけ
        if (!empty($request->menu)) {
            foreach ($request->menu as $item) {
                if (!isset($item['dishIds'])) {
                    continue;
                }
                $data = collect($item['dishIds'])->unique()->map(function ($dishId) use ($ret, $item) {
                    return [
                        'meal_id' => $ret->id,
                        'dish_id' => $dishId,
                        'dish_role_id' => $item['roleId']
                    ];
                })->toArray();
                $ret->dishes()->attach($data);
            }
        }

        return response()->json([
            'id' => $ret->id,
            'date' => $ret->date,
            'category' => [
                "id" => $ret->mealCategory->id,
                "name" => $ret->mealCategory->name,
                "colorId" => $ret->mealCategory->color_id,
            ],
            'menu' => $ret->dishes->groupBy('pivot.dish_role_id')->map(function ($dishes, $roleId) {
                $role = DishRole::find($roleId);
                return [
                    'role' => [
                        "id" => $role->id,
                        "name" => $role->name
                    ],
                    'dishes' => $dishes->map(fn($dish) => [
                        'id' => $dish->id,
                        'name' => $dish->name,
                        'thumbnailUrl' => $dish->thumbnail_url,
                        'url' => $dish->url,
                        'recipe' => $dish->recipe,
                        'memo' => $dish->memo,
                        'categories' => $dish->categories->map(fn($category) => [
                            'id' => $category->id,
                            'name' => $category->name
                        ]),
                        'seasonings' => $dish->seasonings->map(fn($seasoning) => [
                            'id' => $seasoning->id,
                            'name' => $seasoning->name,
                            'quantity' => $seasoning->pivot->quantity,
                            'unitId' => $seasoning->pivot->unit_id
                        ]),
                        'ingredients' => $dish->ingredients->map(fn($ingredient) => [
                            'id' => $ingredient->id,
                            'name' => $ingredient->name,
                            'quantity' => $ingredient->pivot->quantity,
                            'unitId' => $ingredient->pivot->unit_id
                        ])
                    ])
                ];
            })->values(),
        ], 200);
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
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $meal = Meal::where('id', $id)->where('group_id', $group->id)->with(['mealCategory', 'dishes.dishRoles', 'dishes.categories', 'dishes.seasonings', 'dishes.ingredients'])->first();
        if (!$meal) {
            return response()->json([
                'message' => '指定されたレコードが見つかりません。'
            ], 404);
        }

        $res = [
            'id' => $meal->id,
            'date' => $meal->date,
            'category' => [
                "id" => $meal->mealCategory->id,
                "name" => $meal->mealCategory->name,
                "colorId" => $meal->mealCategory->color_id,
            ],
            'menu' => $meal->dishes->groupBy('pivot.dish_role_id')->map(function ($dishes, $roleId) {
                $role = DishRole::find($roleId);
                return [
                    'role' => [
                        'id' => $role->id,
                        'name' => $role->name
                    ],
                    'dishes' => $dishes->map(fn($dish) => [
                        'id' => $dish->id,
                        'name' => $dish->name,
                        'thumbnailUrl' => $dish->thumbnail_url,
                        'url' => $dish->url,
                        'recipe' => $dish->recipe,
                        'memo' => $dish->memo,
                        'categories' => $dish->categories->map(fn($category) => [
                            'id' => $category->id,
                            'name' => $category->name
                        ]),
                        'seasonings' => $dish->seasonings->map(fn($seasoning) => [
                            'id' => $seasoning->id,
                            'name' => $seasoning->name,
                            'quantity' => $seasoning->pivot->quantity,
                            'unitId' => $seasoning->pivot->unit_id
                        ]),
                        'ingredients' => $dish->ingredients->map(fn($ingredient) => [
                            'id' => $ingredient->id,
                            'name' => $ingredient->name,
                            'quantity' => $ingredient->pivot->quantity,
                            'unitId' => $ingredient->pivot->unit_id
                        ])
                    ])
                ];
            })->values()
        ];

        return response()->json($res, 200);
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
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $meal =  Meal::where('id', $id)->where('group_id', $group->id)->first();
        if (!$meal) {
            return response()->json([
                'message' => '指定されたレコードが見つかりません。'
            ], 404);
        }

        $meal->update([
            'group_id' => $group->id,
            'meal_category_id' => $request->categoryId,
            'date' => $request->date,
        ]);

        // 料理更新
        if (!empty($request->menu)) {
            foreach ($request->menu as $item) {
                if (!isset($item['dishIds'])) {
                    continue;
                }
                $data = collect($item['dishIds'])
                    ->unique()
                    ->map(function ($dishId) use ($meal, $item) {
                        return [
                            'meal_id' => $meal->id,
                            'dish_id' => $dishId,
                            'dish_role_id' => $item['roleId']
                        ];
                    })->toArray();
                $meal->dishes()->sync($data);
            }
        }

        $updatedItem = $group->meals()->where('id', $id)->first()->select('id', 'date', 'meal_category_id')->with(['mealCategory', 'dishes.dishRoles', 'dishes.categories', 'dishes.seasonings', 'dishes.ingredients'])->first();

        return response()->json([
            'id' => $updatedItem->id,
            'date' => $updatedItem->date,
            'category' => [
                "id" => $updatedItem->mealCategory->id,
                "name" => $updatedItem->mealCategory->name,
                "colorId" => $updatedItem->mealCategory->color_id,
            ],
            'menu' => $updatedItem->dishes->groupBy('pivot.dish_role_id')->map(function ($dishes, $roleId) {
                $role = DishRole::find($roleId);
                return [
                    'role' => [
                        "id" => $role->id,
                        "name" => $role->name
                    ],
                    'dishes' => $dishes->map(fn($dish) => [
                        'id' => $dish->id,
                        'name' => $dish->name,
                        'thumbnailUrl' => $dish->thumbnail_url,
                        'url' => $dish->url,
                        'recipe' => $dish->recipe,
                        'memo' => $dish->memo,
                        'categories' => $dish->categories->map(fn($category) => [
                            'id' => $category->id,
                            'name' => $category->name
                        ]),
                        'seasonings' => $dish->seasonings->map(fn($seasoning) => [
                            'id' => $seasoning->id,
                            'name' => $seasoning->name,
                            'quantity' => $seasoning->pivot->quantity,
                            'unitId' => $seasoning->pivot->unit_id
                        ]),
                        'ingredients' => $dish->ingredients->map(fn($ingredient) => [
                            'id' => $ingredient->id,
                            'name' => $ingredient->name,
                            'quantity' => $ingredient->pivot->quantity,
                            'unitId' => $ingredient->pivot->unit_id
                        ])
                    ])
                ];
            })->values(),
        ], 200);
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
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $meal =  Meal::where('id', $id)->where('group_id', $group->id)->first();

        if (!$meal) {
            return response()->json([
                'message' => '指定されたレコードが見つかりません。'
            ], 404);
        }

        $deletedId = $meal->id;
        $meal->delete();

        return response()->json(['id' => $deletedId], 200);
    }
}
