<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Services\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupUsersController extends ApiController
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
    public function index(Request $request): JsonResponse
    {
        try {
            // 同じグループに属するユーザーデータを取得
            $users = $request->user()->group->users->map(function ($user) {
                return $this->userService->formatUserInfo($user);
            });

            return $this->indexResponse($users, $users->count(), __('api.users.list_retrieved', ['count' => $users->count()]));
        } catch (Exception $e) {
            $this->logError(__('operations.users.index'), $e, $request);
            return $this->handleException($e, $request, __('api.users.get_failed'));
        }
    }
}
