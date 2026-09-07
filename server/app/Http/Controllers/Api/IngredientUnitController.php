<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Api\IngredientUnitIndexRequest;
use App\Services\IngredientUnitService;

class IngredientUnitController extends ApiController
{
    public function __construct(
        private IngredientUnitService $ingredientUnitService
    ) {}

    /**
     * @OA\Get(
     *     path="/ingredient-units",
     *     summary="食材単位一覧を取得",
     *     tags={"Ingredients"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/IngredientUnitIndexSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(IngredientUnitIndexRequest $request): JsonResponse
    {
        $operation = __('operations.ingredient_unit.index');
        $failedMessage = __('api.get_failed', ['attribute' => __('api.attributes.ingredient_unit')]);
        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $res = $this->ingredientUnitService->index($this->getUserGroup($request));
                $total = count($res);
                $message = __('api.list_retrieved', ['attribute' => __('api.attributes.ingredient_unit'), 'count' => $total]);
                return $this->indexResponse($res, $total, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
