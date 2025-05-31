<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupUsersController extends Controller
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
        $user = $request->user();
        $groupId = $user->groupUser->group_id;

        if (!$groupId) {
            return response()->json(null);
        }

        // 同じグループに属するユーザーデータを取得
        $users = GroupUser::where('group_id', $groupId)
            ->with('user:id,custom_id,name')
            ->get()
            ->pluck('user');

        return response()->json($users, 200);
    }
}
