<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Services\InvitationTokenService;
use App\Services\MasterService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Enums\HttpStatusCode;
use App\Http\Requests\Api\InvitationJoinRequest;
use App\Http\Requests\Api\InvitationShowRequest;
use App\Http\Requests\Api\InvitationStoreRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvitationController extends ApiController
{
    protected UserService $userService;
    protected InvitationTokenService $invitationTokenService;

    public function __construct(
        UserService $userService,
        InvitationTokenService $invitationTokenService
    ) {
        $this->userService = $userService;
        $this->invitationTokenService = $invitationTokenService;
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
    public function store(InvitationStoreRequest $request): JsonResponse
    {
        $operation = __('operations.invitation.store');
        $failedMessage = __('api.invitation.token_generation_failed');

        return $this->executeWithExceptionHandling(
            function () {
                return DB::transaction(function () {
                    $expiresAt = Carbon::now()->addHour(); // 現在時刻から1時間後
                    // トークンをデータベースに保存
                    $invitationToken = $this->invitationTokenService->createWithExpiration(auth()->id(), $expiresAt);
                    if (!$invitationToken) {
                        throw new HttpException(HttpStatusCode::INTERNAL_SERVER_ERROR->value, __('api.invitation.token_generation_failed'));
                    }
                    $data = [
                        'token' => $invitationToken,
                        'expires_at' => $expiresAt
                    ];
                    $message = __('api.invitation.token_generated');
                    return $this->createdResponse($data, $message);
                });
            },
            $request,
            $failedMessage,
            $operation
        );
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
    public function show(InvitationShowRequest $request): JsonResponse
    {
        $operation = __('operations.invitation.show');
        $failedMessage = __('api.invitation.details_retrieval_failed');

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                return DB::transaction(function () use ($request) {
                    $invitationToken = $request->getInvitationToken();
                    $plainToken = $request->route('token');
                    $data = $this->invitationTokenService->formatShowResponse($invitationToken, $plainToken);
                    $message = __('api.invitation.details_retrieved');
                    return $this->showResponse($data, $message);
                });
            },
            $request,
            $failedMessage,
            $operation
        );
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
    public function join(InvitationJoinRequest $request, $token): JsonResponse
    {
        $operation = __('operations.invitation.join');
        $failedMessage = __('api.invitation.join_failed');

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                return DB::transaction(function () use ($request) {
                    $invitationToken = $request->getInvitationToken();
                    $user = $request->user();
                    $currentGroup = $user->groups()->first();
                    $joinGroup = $invitationToken->inviter->groups()->first();

                    // 既存のグループユーザーマッピングを削除
                    $currentGroup->users()->detach($user->id);

                    // 新しいグループユーザーマッピングを作成
                    $joinGroup->users()->attach($user->id);

                    // グループサイズを更新
                    $joinGroup->refreshGroupSize();
                    $currentGroup->refreshGroupSize();

                    // マスターキャッシュを破棄（参加先・離脱元の両方）
                    MasterService::forgetGroupCache($joinGroup);
                    MasterService::forgetGroupCache($currentGroup);

                    // 使用済みトークンを削除（再利用防止）
                    $invitationToken->delete();

                    $message = __('api.invitation.joined_successfully');

                    return $this->successResponse(null, $message);
                });
            },
            $request,
            $failedMessage,
            $operation,
            ['token' => $token]
        );
    }
}
