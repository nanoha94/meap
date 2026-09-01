<?php

namespace App\Services;

use App\Models\InvitationToken;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class InvitationTokenService
{
    public const TOKEN_LOOKUP_LENGTH = 8;

    public function __construct(
        private UserService $userService
    ) {}

    /**
     * 平文トークンから DB ルックアップ用の prefix を抽出する
     */
    public static function extractTokenLookup(string $plainToken): string
    {
        return substr($plainToken, 0, self::TOKEN_LOOKUP_LENGTH);
    }

    /**
     * ランダムなトークンを生成
     */
    public function generateToken(): string
    {
        return Str::random(32);
    }

    /**
     * 平文トークンに一致する招待トークンを取得する
     */
    public function findByPlainToken(string $plainToken): ?InvitationToken
    {
        $lookup = self::extractTokenLookup($plainToken);

        return InvitationToken::where('token_lookup', $lookup)
            ->get()
            ->first(fn(InvitationToken $record) => Hash::check($plainToken, $record->token));
    }

    /**
     * 有効期限付きの招待トークンを作成
     *
     * @param string $inviterId 招待者のユーザーID
     * @param Carbon $expiresAt 有効期限
     * @return string|null 生成されたトークン（プレーンテキスト）、失敗時は null
     */
    public function createWithExpiration(string $inviterId, Carbon $expiresAt): ?string
    {
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $token = $this->generateToken();
            $lookup = self::extractTokenLookup($token);

            // 同一平文トークンが既に保存されていれば、次のトークンを生成する
            if ($this->plainTokenExists($token, $lookup)) {
                continue;
            }

            // 同一平文トークンが既に保存されていなければ、招待トークンを作成する
            InvitationToken::create([
                'inviter_user_id' => $inviterId,
                'token' => Hash::make($token),
                'token_lookup' => $lookup,
                'expires_at' => $expiresAt,
            ]);

            return $token;
        }

        return null;
    }

    /**
     * 招待トークンの詳細をフォーマット
     *
     * @param InvitationToken $item 招待トークン
     * @param string $plainToken 平文トークン
     * @return array<string, mixed>
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

    /**
     * 同一平文トークンが既に保存されていないか確認する
     * 
     * @param string $plainToken 平文トークン
     */
    private function plainTokenExists(string $plainToken, string $lookup): bool
    {
        return InvitationToken::where('token_lookup', $lookup)
            ->get()
            ->contains(fn(InvitationToken $record) => Hash::check($plainToken, $record->token));
    }
}
