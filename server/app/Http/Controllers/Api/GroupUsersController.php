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
     *     summary="同じグループに属するユーザー一覧を取得",
     *     description="認証ユーザーと同じグループに属するユーザーの一覧を返します。",
     *     tags={"User"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="成功",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="custom_id", type="string", example="abc123"),
     *                 @OA\Property(property="name", type="string", example="山田太郎")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="認証エラー"
     *     )
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
