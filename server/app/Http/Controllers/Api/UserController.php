<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\UserIndexRequest;
use App\Http\Requests\Api\UserUpdateRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends ApiController
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
    public function index(UserIndexRequest $request): JsonResponse
    {
        $operation = __('operations.users.index');
        $failedMessage = __('api.get_failed', ['attribute' => __('api.attributes.user')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $res = $this->userService->index($this->getUserGroup($request));
                $total = count($res);
                $message = __('api.list_retrieved', ['attribute' => __('api.attributes.user'), 'count' => $total]);

                return $this->indexResponse($res, $total, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

    /**
     * @OA\Get(
     *     path="/user",
     *     summary="認証ユーザー情報を取得",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200, ref="#/components/responses/UserShowSuccess"
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     * )
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('avatarImage');
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'avatar' => $this->userService->formatUserAvatar($user),
        ];
        $message = __('api.retrieved', ['attribute' => __('api.attributes.user')]);
        return $this->showResponse($data, $message);
    }

    /**
     * @OA\Put(
     *     path="/user",
     *     summary="認証ユーザーのプロフィールを更新",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="新しい名前"),
     *             @OA\Property(property="avatar_image_id", type="string", nullable=true, example="550e8400-e29b-41d4-a716-446655440000"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200, ref="#/components/responses/UserUpdateSuccess"
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors"),
     * )
     */
    public function update(UserUpdateRequest $request): JsonResponse
    {
        $operation = __('operations.users.update');
        $failedMessage = __('api.update_failed', ['attribute' => __('api.attributes.user')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $this->userService->updateProfile($request->user(), $request->validated());
                $message = __('api.updated', ['attribute' => __('api.attributes.user'), 'name' => $request->user()->name]);
                return $this->updatedResponse(null, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
