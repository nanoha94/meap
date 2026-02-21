<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\RecipeDestroyRequest;
use App\Http\Requests\Api\RecipeIndexRequest;
use App\Http\Requests\Api\RecipeShowRequest;
use App\Http\Requests\Api\RecipeStoreRequest;
use App\Http\Requests\Api\RecipeUpdateRequest;
use App\Services\ImageService;
use App\Services\RecipeService;
use App\Traits\AutoComplement;
use Illuminate\Http\JsonResponse;

class RecipeController extends ApiController
{
    use AutoComplement;

    protected ImageService $imageService;
    protected RecipeService $recipeService;

    public function __construct(ImageService $imageService, RecipeService $recipeService)
    {
        $this->imageService = $imageService;
        $this->recipeService = $recipeService;
    }

    /**
     * @OA\Get(
     *     path="/recipes",
     *     summary="料理一覧を取得",
     *     tags={"Recipes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/RecipePageParam"),
     *     @OA\Parameter(ref="#/components/parameters/RecipePerPageParam"),
     *     @OA\Parameter(ref="#/components/parameters/RecipeSortParam"),
     *     @OA\Parameter(ref="#/components/parameters/RecipeOrderParam"),
     *     @OA\Response(response=200, ref="#/components/responses/RecipeIndexSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(RecipeIndexRequest $request): JsonResponse
    {
        $operation = __('operations.recipe.index');
        $failedMessage = __('api.get_failed', ['attribute' => __('api.attributes.recipe')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $user = $request->user();
                $group = $user->groups()->first();

                // ページネーションのパラメータを取得（デフォルト値も設定）
                $perPage = $request->input('per_page', 15);
                $page = $request->input('page', 1);
                // 並び替えパラメータ
                $sort = $request->input('sort', 'created_at');
                $order = $request->input('order', 'desc');

                // TODO: 将来的に無限スクロール対応を検討（現在は全件取得）
                $res = $this->recipeService->index($group, $sort, $order);
                $total = count($res);
                $message = __('api.list_retrieved', ['attribute' => __('api.attributes.recipe'), 'count' => $total]);
                return $this->indexResponse($res, $total, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

    /**
     * @OA\Post(
     *     path="/recipes",
     *     summary="料理を作成",
     *     tags={"Recipes"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/RecipeRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/RecipeStoreSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function store(RecipeStoreRequest $request): JsonResponse
    {
        $operation = __('operations.recipe.store');
        $failedMessage = __('api.creation_failed', ['attribute' => __('api.attributes.recipe')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $this->recipeService->create($request->validated(), $this->getUserGroup($request));
                $message = __('api.created', ['attribute' => __('api.attributes.recipe'), 'name' => $request->name]);
                return $this->createdResponse(null, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

    /**
     * @OA\Get(
     *     path="/recipes/{id}",
     *     summary="料理の詳細を取得",
     *     tags={"Recipes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/RecipeIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/RecipeShowSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(RecipeShowRequest $request, string $id): JsonResponse
    {
        $operation = __('operations.recipe.show');
        $failedMessage = __('api.get_failed', ['attribute' => __('api.attributes.recipe')]);

        return $this->executeWithExceptionHandling(
            function () use ($request, $id) {
                $res = $this->recipeService->show($id, $this->getUserGroup($request));
                $message = __('api.retrieved', ['attribute' => __('api.attributes.recipe'), 'name' => $res['name']]);
                return $this->showResponse($res, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

    /**
     * @OA\Put(
     *     path="/recipes/{id}",
     *     summary="料理を更新",
     *     tags={"Recipes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/RecipeIdParam"),
     *     @OA\RequestBody(ref="#/components/requestBodies/RecipeRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/RecipeUpdateSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function update(RecipeUpdateRequest $request, string $id): JsonResponse
    {
        $operation = __('operations.recipe.update');
        $failedMessage = __('api.update_failed', ['attribute' => __('api.attributes.recipe')]);

        return $this->executeWithExceptionHandling(
            function () use ($request, $id) {
                $this->recipeService->update(
                    $id,
                    $request->validated(),
                    $this->getUserGroup($request)
                );
                $message = __('api.updated', ['attribute' => __('api.attributes.recipe'), 'name' => $request->name]);
                return $this->updatedResponse(null, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

    /**
     * @OA\Delete(
     *     path="/recipes/{id}",
     *     summary="料理を削除",
     *     tags={"Recipes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/RecipeIdParam"),
     *     @OA\Response(response=200, ref="#/components/responses/RecipeDestroySuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(RecipeDestroyRequest $request, string $id): JsonResponse
    {
        $operation = __('operations.recipe.destroy');
        $failedMessage = __('api.deletion_failed', ['attribute' => __('api.attributes.recipe')]);

        return $this->executeWithExceptionHandling(
            function () use ($request, $id) {
                $deletedRecipe = $this->recipeService->delete($id, $this->getUserGroup($request));
                $message = __('api.deleted', ['attribute' => __('api.attributes.recipe'), 'name' => $deletedRecipe->name ?? '']);
                return $this->deletedResponse($message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
