<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Models\IngredientCategory;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IngredientCategoryController extends ApiController
{
    /**
     * @OA\Get(
     *     path="/ingredient-categories",
     *     summary="食材カテゴリ一覧を取得",
     *     tags={"Ingredients"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/IngredientCategoryIndexSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            $categories = $group->ingredientCategories()
                ->select('id', 'name', 'order')
                ->orderBy('order', 'asc')
                ->get();

            $formattedData = $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'order' => $category->order
                ];
            });

            return $this->indexResponse($formattedData, $categories->count(), '食材カテゴリー一覧を取得しました。');
        } catch (Exception $e) {
            return $this->handleException($e, '食材カテゴリーの取得中にエラーが発生しました。');
        }
    }

    /**
     * @OA\Post(
     *     path="/ingredient-categories",
     *     summary="食材カテゴリを作成",
     *     tags={"Ingredients"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/IngredientCategoryStoreRequest"),
     *     @OA\Response(response=201, ref="#/components/responses/IngredientCategoryStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            // 入力値のバリデーション
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'order' => 'required|integer|min:0',
            ]);

            $category = IngredientCategory::create([
                'group_id' => $group->id,
                'name' => $validated['name'],
                'order' => $validated['order'],
            ]);

            $data = [
                'id' => $category->id,
                'name' => $category->name,
                'order' => $category->order
            ];

            return $this->createdResponse($data, '食材カテゴリー「' . $validated['name'] . '」を作成しました。');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e, '食材カテゴリーの作成中にエラーが発生しました。');
        }
    }

    /**
     * @OA\Put(
     *     path="/ingredient-categories/bulk",
     *     summary="食材カテゴリを一括更新",
     *     tags={"Ingredients"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/IngredientCategoryBulkUpdateRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/IngredientCategoryBulkUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            $validated = $request->validate([
                'data' => 'required|array',
                'data.*.id' => 'required|string|max:255',
                'data.*.name' => 'required|string|max:255',
                'data.*.order' => 'required|integer|min:0',
            ]);

            $updatedCount = 0;
            // 更新処理を実行
            foreach ($request->data as $category) {
                $ret = IngredientCategory::where('id', $category['id'])->where('group_id', $group->id)->update([
                    'name' => $category['name'],
                    'order' => $category['order']
                ]);
                if (!$ret) {
                    throw new Exception('食材カテゴリー（' . $category['name'] . '）の更新に失敗しました。');
                } else {
                    $updatedCount++;
                }
            }

            return $this->updatedResponse(
                $updatedCount . '件の食材カテゴリーを更新しました。'
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e, '食材カテゴリーの一括更新中にエラーが発生しました。');
        }
    }

    /**
     * @OA\Delete(
     *     path="/ingredient-categories/bulk",
     *     summary="食材カテゴリを削除",
     *     tags={"Ingredients"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/IngredientCategoryBulkDestroyRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/IngredientCategoryBulkDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            // 入力値のバリデーション
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'string|exists:ingredient_categories,id',
            ]);

            $deletedCount = $group->ingredientCategories()
                ->whereIn('id', $validated['ids'])
                ->delete();

            // 残りのカテゴリーのorderを整理
            $remainingCategories = IngredientCategory::where('group_id', $group->id)
                ->orderBy('order')
                ->get();

            foreach ($remainingCategories as $index => $remainingCategory) {
                $remainingCategory->update(['order' => $index]);
            }

            return $this->deletedResponse($deletedCount . '件の食材カテゴリーを削除しました。');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->handleException($e, '食材カテゴリーの削除中にエラーが発生しました。');
        }
    }
}
