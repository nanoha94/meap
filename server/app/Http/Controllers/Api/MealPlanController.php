<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseType;
use App\Models\MealPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MealPlanController extends Controller
{
    /**
     * @OA\Get(
     *     path="/meal-plans",
     *     summary="献立一覧を取得",   
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/MealPlanIndexSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        // TODO: 月別に取得できるようにする
        $meal_plans = $group->mealPlans()
            ->select('id', 'date', 'meal_type_id')
            ->with(['mealType', 'recipes.courseTypes', 'recipes.categories', 'recipes.seasonings', 'recipes.ingredients'])
            ->get()
            ->groupBy('date')
            ->values();

        $res = [
            'mealPlans' => $meal_plans->map(function ($dateMeals, $date) {
                return [
                    'date' => $date,
                    'mealPlans' => $dateMeals->map(function ($mealPlan) {
                        return [
                            'id' => $mealPlan->id,
                            'date' => $mealPlan->date,
                            'category' => [
                                "id" => $mealPlan->mealType->id,
                                "name" => $mealPlan->mealType->name,
                                "colorId" => $mealPlan->mealType->color_id,
                            ],
                            'menu' => $mealPlan->recipes->groupBy('pivot.course_type_id')->map(function ($recipes, $courseTypeId) {
                                $courseType = CourseType::find($courseTypeId);
                                return [
                                    'courseType' => [
                                        'id' => $courseType->id,
                                        'name' => $courseType->name
                                    ],
                                    'recipes' => $recipes->map(fn($recipe) => [
                                        'id' => $recipe->id,
                                        'name' => $recipe->name,
                                        'thumbnailUrl' => $recipe->thumbnail_url,
                                        'url' => $recipe->url,
                                        'recipe' => $recipe->recipe,
                                        'memo' => $recipe->memo,
                                        'categories' => $recipe->categories->map(fn($category) => [
                                            'id' => $category->id,
                                            'name' => $category->name,
                                            'order' => $category->order
                                        ]),
                                        'seasonings' => $recipe->seasonings->map(fn($seasoning) => [
                                            'id' => $seasoning->id,
                                            'name' => $seasoning->name,
                                            'quantity' => $seasoning->pivot->quantity,
                                            'unitId' => $seasoning->pivot->unit_id,
                                            'order' => $seasoning->pivot->order
                                        ]),
                                        'ingredients' => $recipe->ingredients->map(fn($ingredient) => [
                                            'id' => $ingredient->id,
                                            'name' => $ingredient->name,
                                            'quantity' => $ingredient->pivot->quantity,
                                            'unitId' => $ingredient->pivot->unit_id,
                                            'order' => $ingredient->pivot->order
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
     *     path="/meal-plans",
     *     summary="献立を作成",
     *     description="献立を作成します。",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/MealPlanRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/MealPlanStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $ret = MealPlan::create([
            'group_id' => $group->id,
            'meal_type_id' => $request->mealTypeId,
            'date' => $request->date,
        ]);

        // 料理を紐づけ
        if (!empty($request->menu)) {
            foreach ($request->menu as $item) {
                if (!isset($item['recipeIds'])) {
                    continue;
                }
                $data = collect($item['recipeIds'])->unique()->map(function ($recipeId) use ($ret, $item) {
                    return [
                        'meal_plan_id' => $ret->id,
                        'recipe_id' => $recipeId,
                        'course_type_id' => $item['courseTypeId']
                    ];
                })->toArray();
                $ret->recipes()->attach($data);
            }
        }

        return response()->json([
            'id' => $ret->id,
            'date' => $ret->date,
            'category' => [
                "id" => $ret->mealType->id,
                "name" => $ret->mealType->name,
                "colorId" => $ret->mealType->color_id,
            ],
            'menu' => $ret->recipes->groupBy('pivot.course_type_id')->map(function ($recipes, $courseTypeId) {
                $courseType = CourseType::find($courseTypeId);
                return [
                    'courseType' => [
                        "id" => $courseType->id,
                        "name" => $courseType->name
                    ],
                    'recipes' => $recipes->map(fn($recipe) => [
                        'id' => $recipe->id,
                        'name' => $recipe->name,
                        'thumbnailUrl' => $recipe->thumbnail_url,
                        'url' => $recipe->url,
                        'recipe' => $recipe->recipe,
                        'memo' => $recipe->memo,
                        'categories' => $recipe->categories->map(fn($category) => [
                            'id' => $category->id,
                            'name' => $category->name,
                            'order' => $category->order
                        ]),
                        'seasonings' => $recipe->seasonings->map(fn($seasoning) => [
                            'id' => $seasoning->id,
                            'name' => $seasoning->name,
                            'quantity' => $seasoning->pivot->quantity,
                            'unitId' => $seasoning->pivot->unit_id,
                            'order' => $seasoning->pivot->order
                        ]),
                        'ingredients' => $recipe->ingredients->map(fn($ingredient) => [
                            'id' => $ingredient->id,
                            'name' => $ingredient->name,
                            'quantity' => $ingredient->pivot->quantity,
                            'unitId' => $ingredient->pivot->unit_id,
                            'order' => $ingredient->pivot->order
                        ])
                    ])
                ];
            })->values(),
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/meal-plans/{id}",
     *     summary="献立の詳細を取得",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/MealPlanIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/MealPlanShowSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $meal = MealPlan::where('id', $id)->where('group_id', $group->id)->with(['mealType', 'recipes.courseTypes', 'recipes.categories', 'recipes.seasonings', 'recipes.ingredients'])->first();
        if (!$meal) {
            return response()->json([
                'message' => '指定されたレコードが見つかりません。'
            ], 404);
        }

        $res = [
            'id' => $meal->id,
            'date' => $meal->date,
            'category' => [
                "id" => $meal->mealType->id,
                "name" => $meal->mealType->name,
                "colorId" => $meal->mealType->color_id,
            ],
            'menu' => $meal->recipes->groupBy('pivot.course_type_id')->map(function ($recipes, $courseTypeId) {
                $courseType = CourseType::find($courseTypeId);
                return [
                    'courseType' => [
                        'id' => $courseType->id,
                        'name' => $courseType->name
                    ],
                    'recipes' => $recipes->map(fn($recipe) => [
                        'id' => $recipe->id,
                        'name' => $recipe->name,
                        'thumbnailUrl' => $recipe->thumbnail_url,
                        'url' => $recipe->url,
                        'recipe' => $recipe->recipe,
                        'memo' => $recipe->memo,
                        'categories' => $recipe->categories->map(fn($category) => [
                            'id' => $category->id,
                            'name' => $category->name,
                            'order' => $category->order
                        ]),
                        'seasonings' => $recipe->seasonings->map(fn($seasoning) => [
                            'id' => $seasoning->id,
                            'name' => $seasoning->name,
                            'quantity' => $seasoning->pivot->quantity,
                            'unitId' => $seasoning->pivot->unit_id,
                            'order' => $seasoning->pivot->order
                        ]),
                        'ingredients' => $recipe->ingredients->map(fn($ingredient) => [
                            'id' => $ingredient->id,
                            'name' => $ingredient->name,
                            'quantity' => $ingredient->pivot->quantity,
                            'unitId' => $ingredient->pivot->unit_id,
                            'order' => $ingredient->pivot->order
                        ])
                    ])
                ];
            })->values()
        ];

        return response()->json($res, 200);
    }

    /**
     * @OA\Put(
     *     path="/meal-plans/{id}",
     *     summary="献立を更新",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/MealPlanIdParam"),
     *     @OA\RequestBody(ref="#/components/requestBodies/MealPlanRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/MealPlanUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $meal =  MealPlan::where('id', $id)->where('group_id', $group->id)->first();
        if (!$meal) {
            return response()->json([
                'message' => '指定されたレコードが見つかりません。'
            ], 404);
        }

        $meal->update([
            'group_id' => $group->id,
            'meal_type_id' => $request->mealTypeId,
            'date' => $request->date,
        ]);

        // 料理更新
        if (!empty($request->menu)) {
            foreach ($request->menu as $item) {
                if (!isset($item['recipeIds'])) {
                    continue;
                }
                $data = collect($item['recipeIds'])
                    ->unique()
                    ->map(function ($recipeId) use ($meal, $item) {
                        return [
                            'meal_plan_id' => $meal->id,
                            'recipe_id' => $recipeId,
                            'course_type_id' => $item['courseTypeId']
                        ];
                    })->toArray();
                $meal->recipes()->sync($data);
            }
        }

        $updatedItem = $group->mealPlans()->where('id', $id)->first()->select('id', 'date', 'meal_type_id')->with(['mealType', 'recipes.courseTypes', 'recipes.categories', 'recipes.seasonings', 'recipes.ingredients'])->first();

        return response()->json([
            'id' => $updatedItem->id,
            'date' => $updatedItem->date,
            'category' => [
                "id" => $updatedItem->mealType->id,
                "name" => $updatedItem->mealType->name,
                "colorId" => $updatedItem->mealType->color_id,
            ],
            'menu' => $updatedItem->recipes->groupBy('pivot.course_type_id')->map(function ($recipes, $courseTypeId) {
                $courseType = CourseType::find($courseTypeId);
                return [
                    'courseType' => [
                        "id" => $courseType->id,
                        "name" => $courseType->name
                    ],
                    'recipes' => $recipes->map(fn($recipe) => [
                        'id' => $recipe->id,
                        'name' => $recipe->name,
                        'thumbnailUrl' => $recipe->thumbnail_url,
                        'url' => $recipe->url,
                        'recipe' => $recipe->recipe,
                        'memo' => $recipe->memo,
                        'categories' => $recipe->categories->map(fn($category) => [
                            'id' => $category->id,
                            'name' => $category->name,
                            'order' => $category->order
                        ]),
                        'seasonings' => $recipe->seasonings->map(fn($seasoning) => [
                            'id' => $seasoning->id,
                            'name' => $seasoning->name,
                            'quantity' => $seasoning->pivot->quantity,
                            'unitId' => $seasoning->pivot->unit_id,
                            'order' => $seasoning->pivot->order
                        ]),
                        'ingredients' => $recipe->ingredients->map(fn($ingredient) => [
                            'id' => $ingredient->id,
                            'name' => $ingredient->name,
                            'quantity' => $ingredient->pivot->quantity,
                            'unitId' => $ingredient->pivot->unit_id,
                            'order' => $ingredient->pivot->order
                        ])
                    ])
                ];
            })->values(),
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/meal-plans/{id}",
     *     summary="献立を削除",
     *     tags={"MealPlans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/MealPlanIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/MealPlanDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;

        $meal =  MealPlan::where('id', $id)->where('group_id', $group->id)->first();

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
