<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\InvitationToken;
use App\Models\Group;
use App\Models\ShoppingCategory;
use App\Models\ShoppingItem;
use Illuminate\Support\Facades\Hash;

class GroupUsersController extends Controller
{
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

        return response()->json($users);
    }

    public function join(Request $request, $token)
    {
        // 有効期限が切れていないトークンを取得
        $invitationToken = InvitationToken::where('expires_at', '>=', now())->get()->first(function ($record) use ($token) {
            return Hash::check($token, $record->token);
        });

        if (!$invitationToken) {
            return response()->json(['message' => '無効なトークンです。'], 403);
        }

        // ログインしているユーザーを取得
        $user = $request->user();
        $currentGroup = $user->groupUser->group;

        // 招待者
        $inviter = $invitationToken->inviter;

        if ($invitationToken->inviter_id === $user->id) {
            return response()->json(['message' => '自分自身を招待することはできません。'], 403);
        }

        // 招待された人がすでに同じグループにいるかチェック
        if ($currentGroup->id === $inviter->groupUser->group_id) {
            return response()->json(['message' => 'すでにグループに参加しています。'], 403);
        }

        // 所属しているグループがあるかチェック
        if ($currentGroup->group_size > 1) {
            return response()->json(['message' => 'すでに別のグループに所属しています。'], 409);
        }

        // データがあるかチェック
        if (!$request->isDelete) {
            if (ShoppingItem::where('group_id', $currentGroup->id)->exists()) {
                return response()->json(['message' => 'すでに登録済みのデータがあります。'], 409);
            }
            if (ShoppingCategory::where('group_id',  $currentGroup->id)->exists()) {
                return response()->json(['message' => 'すでに登録済みのデータがあります。'], 409);
            }
        }

        // 招待された人を同じグループに追加
        $group = $inviter->groupUser->group;
        // // 招待された人を同じグループに追加
        $group = $inviter->groupUser->group;
        $user->groupUser->group_id = $group->id;
        $user->groupUser->save();

        $group->group_size = Group::getGroupSize($group->id);
        $group->save();


        // // 招待された人のグループを削除
        $currentGroup->delete();


        return response()->json(['message' => 'グループに参加しました']);
    }
}
