<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\MasterRequest;
use Illuminate\Http\JsonResponse;

class MasterController extends ApiController
{
    /**
     * @OA\Get(
     *     path="/master",
     *     summary="マスターデータを取得",
     *     tags={"Master"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/MasterSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    // TODO: モデルごとのAPIでGETリクエストを受けるように変更予定
    // グループ内の別ユーザーが更新したデータを毎度取得できるようにするため
    // 今の実装だと、一度に通信するデータ量が多いので、必要なときに必要なだけリクエストできるようにする
    public function __invoke(MasterRequest $request): JsonResponse
    {
        $operation = __('operations.master.index');
        $failedMessage = __('api.get_failed', ['attribute' => __('api.attributes.master')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $user = $request->user();
                $group = $user->group;

                $recipeCategories = $group->recipeCategories()->select('id', 'name', 'order')->orderBy('order', 'asc')->get();
                $ingredientCategories = $group->ingredientCategories()->select('id', 'name', 'order')->orderBy('order', 'asc')->get();
                $ingredientUnits = $group->ingredientUnits()->select('id', 'name', 'position', 'requires_quantity', 'order')->orderBy('order', 'asc')->get();
                $courseTypes = $group->courseTypes()->select('id', 'name', 'order')->get();
                $shopping_categories = $group->shoppingCategories()->select('id', 'name', 'is_default', 'order')->orderBy('order', 'asc')->get();
                $shopping_tags = $group->shoppingTags()->select('id', 'name')->get();
                $res = [
                    'recipeCategories' =>  $recipeCategories,
                    'ingredientCategories' => $ingredientCategories,
                    'ingredientUnits' => $ingredientUnits,
                    'courseTypes' => $courseTypes,
                    'shoppingCategories' => $shopping_categories,
                    'shoppingTags' => $shopping_tags,
                ];

                $message = __('api.retrieved', ['attribute' => __('api.attributes.master')]);

                return $this->successResponse($res, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
