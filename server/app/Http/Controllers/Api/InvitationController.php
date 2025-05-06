<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\InvitationToken;
use App\Models\ShoppingCategory;
use App\Models\ShoppingItem;
use Illuminate\Http\Request;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class InvitationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/invitations",
     *     summary="グループへの招待トークンを生成",
     *     description="グループに招待するためのトークンを生成して返します。",
     *     tags={"Invitations"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=201,
     *         description="成功",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string", example="1234567890"),
     *             @OA\Property(property="expires_at", type="string", example="2025-01-01 00:00:00"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="エラー",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="エラーメッセージ")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $expiresAt = Carbon::now()->addHour(); // 現在時刻から1時間後
            // トークンをデータベースに保存
            $invitationToken = InvitationToken::createWithExpiration(auth()->id(), $expiresAt);
            return response()->json(['token' =>  $invitationToken, 'expires_at' => $expiresAt], 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/invitations/{token}",
     *     summary="招待トークンの詳細を取得",
     *     description="招待トークンの詳細を取得します。",
     *     tags={"Invitations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="招待トークン",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="成功",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string", example="1234567890"),
     *             @OA\Property(property="expires_at", type="string", example="2025-01-01 00:00:00"),
     *             @OA\Property(property="inviter", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="custom_id", type="string", example="abc123"),
     *                 @OA\Property(property="name", type="string", example="山田太郎")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="エラー",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="エラーメッセージ")
     *         )
     *     )
     * )
     */
    public function show(string $token): JsonResponse
    {
        $invitationToken = InvitationToken::all()->first(function ($record) use ($token) {
            return Hash::check($token, $record->token);
        });

        return response()->json([
            'token' => $invitationToken->token,
            'expires_at' => $invitationToken->expires_at,
            'inviter' => [
                'id' => $invitationToken->inviter->id,
                'custom_id' => $invitationToken->inviter->custom_id,
                'name' => $invitationToken->inviter->name
            ]
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/invitations/{token}/join",
     *     summary="招待トークンを使用してグループに参加",
     *     description="招待トークンを使用してグループに参加します。",
     *     tags={"Invitations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(   
     *         name="token",
     *         in="path",
     *         description="招待トークン",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="成功",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="グループに参加しました。")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="エラー",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="エラーメッセージ")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="エラー",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="エラーメッセージ")
     *         )
     *     )
     * )
     */
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

        return response()->json(['message' => 'グループに参加しました。'], 200);
    }
}
