<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\ShoppingTagIndexRequest;
use App\Services\ShoppingTagService;
use Illuminate\Http\JsonResponse;

class ShoppingTagController extends ApiController
{
    private ShoppingTagService $shoppingTagService;

    public function __construct(ShoppingTagService $shoppingTagService)
    {
        $this->shoppingTagService = $shoppingTagService;
    }

    /**
     * @OA\Get(
     *     path="/shopping-tags",
     *     summary="買い物タグ一覧を取得",
     *     tags={"Shopping"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/ShoppingTagIndexSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(ShoppingTagIndexRequest $request): JsonResponse
    {
            $operation = __('operations.shopping_tag.index');
        $failedMessage = __('api.shopping_tag.get_failed');

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $res = $this->shoppingTagService->index($request->user()->group);
                $total = count($res);
                $message = __('api.list_retrieved', ['attribute' => __('api.attributes.shopping.tag'), 'count' => $total]);
                return $this->indexResponse($res, $total, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
