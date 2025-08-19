<?php

namespace App\Services;

class UserService
{
    /**
     * ユーザーのアバター情報をフォーマット
     */
    public function formatUserAvatar($user): array
    {
        return [
            'seed' => $user->avatar_seed,
            'url' => $user->avatar_url,
            'width' => $user->avatar_width,
            'height' => $user->avatar_height,
        ];
    }

    /**
     * ユーザー情報をフォーマット
     */
    public function formatUserInfo($user): array
    {
        return [
            'name' => $user->name,
            'language' => $user->language,
            'avatar' => $this->formatUserAvatar($user),
        ];
    }
}
