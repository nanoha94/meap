<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Models\GroupUserMapping;
use App\Models\InvitationToken;
use App\Services\UserService;
use Illuminate\Http\Request;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Enums\HttpStatusCode;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvitationController extends ApiController
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @OA\ Post(
     *     path="/invitations",
     *     summary="グループへの招待トークンを生成",
     *     tags={"Invitations"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=201,
     *         ref="#/components/responses/InvitationStoreSuccess"
     *     ),
     *     @OA\Response(
     *         response=500, ref="#/components/responses/UnexpectedError"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $expiresAt = Carbon::now()->addHour(); // 現在時刻から1時間後
            // トークンをデータベースに保存
            $invitationToken = InvitationToken::createWithExpiration(auth()->id(), $expiresAt);
            if (!$invitationToken) {
                $this->handleException(
                    new HttpException(HttpStatusCode::INTERNAL_SERVER_ERROR->value, __('api.invitation.token_generation_failed')),
                    $request,
                    __('api.invitation.token_generation_failed'),
                    __('operations.invitation.store')
                );
            }
            $data = [
                'token' => $invitationToken,
                'expires_at' => $expiresAt
            ];
            return $this->createdResponse($data, __('api.auth.invitation_token_created'));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.auth.invitation_token_creation_failed'),
                'invitation.store'
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/invitations/{token}",
     *     summary="招待トークンの詳細を取得",
     *     tags={"Invitations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/InvitationShowParameter"),
     *     @OA\Response(
     *         response=200,
     *         ref="#/components/responses/InvitationShowSuccess"
     *     ),
     *     @OA\Response(
     *         response=500, ref="#/components/responses/UnexpectedError"
     *     )
     * )
     */
    public function show(Request $request, string $token): JsonResponse
    {
        try {
            $user = $request->user();
            $currentGroup = $user->group;

            // TODO: ここで有効期限切れか無効か、自分自身かなどチェックできたほうがいいかも要検討
            // TODO: FormRequestでトークンチェックするか検討
            // 有効期限が切れていないトークンを取得し、ハッシュチェックを行う
            // $invitationToken = InvitationToken::where('expires_at', '>=', now())->get()->first(function ($record) use ($token) {
            //     return Hash::check($token, $record->token);
            // });

            // if (!$invitationToken) {
            //     return $this->errorResponse('無効なトークンです。', 403);
            // }

            $invitationToken = InvitationToken::all()->first(function ($record) use ($token) {
                return Hash::check($token, $record->token);
            });

            if (!$invitationToken) {
                $this->handleException(
                    new HttpException(HttpStatusCode::FORBIDDEN->value, __('api.invitation.invalid_token')),
                    $request,
                    __('api.invitation.invalid_token'),
                    __('operations.invitation.show'),
                    ['token' => $token]
                );
            }

            $data = [
                'token' => $token,
                'expires_at' => $invitationToken->expires_at,
                'inviter' => [
                    'id' => $invitationToken->inviter->id,
                    'name' => $invitationToken->inviter->name,
                    'avatar' => $this->userService->formatUserAvatar($invitationToken->inviter)
                ]
            ];
            return $this->showResponse($data, __('api.invitation.details_retrieved'));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.invitation.get_failed'),
                'invitation.show'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/invitations/{token}/join",
     *     summary="招待トークンを使用してグループに参加",
     *     tags={"Invitations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/InvitationShowParameter"),
     *     @OA\Response(
     *         response=200,
     *         ref="#/components/responses/InvitationJoinSuccess"
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
        try {
            // 有効期限が切れていないトークンを取得
            $invitationToken = InvitationToken::where('expires_at', '>=', now())->get()->first(function ($record) use ($token) {
                return Hash::check($token, $record->token);
            });

            if (!$invitationToken) {
                $this->handleException(
                    new HttpException(HttpStatusCode::FORBIDDEN->value, __('api.invitation.invalid_token')),
                    $request,
                    __('api.invitation.invalid_token'),
                    __('operations.invitation.join'),
                    ['token' => $token]
                );
            }

            // ログインしているユーザーを取得
            $user = $request->user();
            $currentGroup = $user->group;

            // 招待者
            $inviter = $invitationToken->inviter;

            if ($invitationToken->inviter_id === $user->id) {
                $this->handleException(
                    new HttpException(HttpStatusCode::FORBIDDEN->value, __('api.invitation.invalid_token')),
                    $request,
                    __('api.invitation.invalid_token'),
                    __('operations.invitation.join'),
                    ['token' => $token]
                );
            }

            // 招待された人がすでに同じグループにいるかチェック
            if ($currentGroup->id === $inviter->group->id) {
                $this->handleException(
                    new HttpException(HttpStatusCode::FORBIDDEN->value, __('api.invitation.invalid_token')),
                    $request,
                    __('api.invitation.invalid_token'),
                    __('operations.invitation.join'),
                    ['token' => $token]
                );
            }

            if (!$request->isDelete) {
                // 所属しているグループがあるかチェック
                if ($currentGroup->group_size > 1) {
                    $this->handleException(
                        new HttpException(HttpStatusCode::CONFLICT->value, __('api.invitation.already_in_another_group')),
                        $request,
                        __('api.invitation.already_in_another_group'),
                        __('operations.invitation.join'),
                        ['token' => $token]
                    );
                }
                // データがあるかチェック
                if ($currentGroup->shoppingItems()->exists() || $currentGroup->shoppingCategories()->where('is_default', 0)->exists()) {
                    $this->handleException(
                        new HttpException(HttpStatusCode::CONFLICT->value, __('api.invitation.has_existing_data')),
                        $request,
                        __('api.invitation.has_existing_data'),
                        __('operations.invitation.join'),
                        ['token' => $token]
                    );
                }
            }

            // 招待された人を同じグループに追加
            $group = $inviter->group;

            // 既存のグループユーザーマッピングを削除
            $user->groupUser()->delete();

            // 新しいグループユーザーマッピングを作成
            GroupUserMapping::create([
                'user_id' => $user->id,
                'group_id' => $group->id
            ]);

            $group->group_size = $group->getGroupSize();
            $group->save();

            // 現在のグループのサイズを更新
            $currentGroup->group_size = $currentGroup->getGroupSize();
            $currentGroup->save();

            // 現在のグループに所属するユーザーが0人になった場合のみグループを削除
            if ($currentGroup->group_size === 0) {
                $currentGroup->delete();
            }

            return $this->successResponse(null, __('api.invitation.joined_successfully'));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.invitation.join_failed'),
                'invitation.join'
            );
        }
    }
}
