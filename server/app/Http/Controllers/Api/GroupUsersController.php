<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupUsersController extends ApiController
{
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
        // 同じグループに属するユーザーデータを取得
        $users = $request->user()->group->users->map(function ($user) {
            return [
                'name' => $user->name,
                'avatar' => [
                    'seed' => $user->avatar_seed,
                    'url' => $user->avatar_url,
                    'width' => $user->avatar_width,
                    'height' => $user->avatar_height,
                ],
            ];
        });

        return $this->indexResponse($users, $users->count(), __('api.users.list_retrieved', ['count' => $users->count()]));
    }
}
