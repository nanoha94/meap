<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\InvitationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;

class InvitationTokenAcceptController extends Controller
{
    public function __invoke(Request $request, $token): RedirectResponse
    {
        // 有効期限が切れていないトークンを取得
        $invitationToken = InvitationToken::where('expires_at', '>=', now())->get()->first(function ($record) use ($token) {
            return Hash::check($token, $record->token);
        });

        if (!$invitationToken) {
            return redirect()->intended(
                config('app.frontend_url') . '/plan?verified=1'
            );
            // TODO:　フロントエンドにエラーを伝える
            // return response()->json(['message' => '無効なトークンです。'], 403);
        }

        // ログインしているユーザーを取得
        $user = $request->user();

        // TODO: ユーザーがログインしていない場合
        if (!$user) {
            // アカウント作成処理を行う
        }

        // 招待者がグループを持っていない場合はグループを作成
        $inviter = $invitationToken->inviter;
        if ($inviter->groupUser->group_id === null) {
            $group = Group::create([
                'group_size' => Group::getGroupSize($inviter->group_id) + 1,
            ]);
            $inviter->groupUser->group_id = $group->id;
            $inviter->groupUser->save();
        }

        // 招待された人を同じグループに追加
        $user->groupUser->group_id = $inviter->groupUser->group_id;
        $user->groupUser->save();

        // トークンを削除
        $invitationToken->delete();

        return redirect()->intended(
            config('app.frontend_url') . '/settings/account'
        );
    }
}
