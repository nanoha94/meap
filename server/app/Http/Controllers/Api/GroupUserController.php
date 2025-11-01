<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\GroupUserIndexRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class GroupUserController extends ApiController
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @OA\Get(
     *     path="/users",
     *     summary="認証ユーザーと同じグループに属するユーザ一覧を取得",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200, ref="#/components/responses/UserIndexSuccess"
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     * )
     */
    public function index(GroupUserIndexRequest $request): JsonResponse
    {
        $operation = __('operations.users.index');
        $failedMessage = __('api.get_failed', ['attribute' => __('api.attributes.user')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $res = $this->userService->index($request->user()->group);
                $total = count($res);
                $message = __('api.list_retrieved', ['attribute' => __('api.attributes.user'), 'count' => $total]);

                return $this->indexResponse($res, $total, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
