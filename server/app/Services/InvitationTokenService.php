<?php

namespace App\Services;

use App\Models\InvitationToken;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class InvitationTokenService
{

    public function __construct(
        private UserService $userService
    ) {}

    /**
     * ランダムなトークンを生成
     *
     * @return string
     */
    public function generateToken(): string
    {
        return Str::random(32);
    }

    /**
     * 有効期限付きの招待トークンを作成
     *
     * @param string $inviterId 招待者のID
     * @param Carbon $expiresAt 有効期限
     * @return string|null 生成されたトークン（プレーンテキスト）、失敗時はnull
     */
    public function createWithExpiration(string $inviterId, Carbon $expiresAt): ?string
    {
        $maxAttempts = 5; // 最大試行回数
        $attempt = 0;

        do {
            $attempt++;

            $token = $this->generateToken();

            if ($attempt >= $maxAttempts) {
                // 最大試行回数に達した場合はnullを返す
                return null;
            }
        } while (InvitationToken::where('token', $token)->exists());

        // トークンをデータベースに保存
        InvitationToken::create([
            'inviter_user_id' => $inviterId,
            'token' => Hash::make($token),
            'expires_at' => $expiresAt,
        ]);

        return $token;
    }

    /**
     * 招待トークンの詳細をフォーマット
     *
     * @param InvitationToken $item
     * @param string $plainToken プレーンテキストトークン
     * @return array
     */
    public function formatShowResponse(InvitationToken $item, string $plainToken): array
    {
        return [
            'token' => $plainToken,
            'expires_at' => $item->expires_at,
            'inviter' => [
                'id' => $item->inviter->id,
                'name' => $item->inviter->name,
                'avatar' => $this->userService->formatUserAvatar($item->inviter)
            ]
        ];
    }
}
