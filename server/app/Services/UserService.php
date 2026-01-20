<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use App\Services\AbstractDomainService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UserService extends AbstractDomainService
{
    protected function getSelectColumns(): array
    {
        return ['id', 'name', 'language', 'avatar_seed', 'avatar_image_url', 'avatar_image_width', 'avatar_image_height'];
    }

    protected function getResourceName(): string
    {
        return __('api.attributes.user');
    }

    protected function getGroupRelation(Group $group): BelongsToMany
    {
        return $group->users();
    }

    protected function formatIndexResponse(Model|Collection $item): array
    {
        // 型チェック
        $this->typeCheck($item, User::class);

        return [
            'id' => $item->id,
            'name' => $item->name,
            'language' => $item->language,
            'avatar' => $this->formatUserAvatar($item),
        ];
    }

    /**
     * ユーザーのアバター情報をフォーマット
     */
    public function formatUserAvatar($user): array
    {
        return [
            'seed' => $user->avatar_seed,
            'url' => $user->avatar_image_url,
            'width' => $user->avatar_image_width,
            'height' => $user->avatar_image_height,
        ];
    }
}
