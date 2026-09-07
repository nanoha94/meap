<?php

namespace App\Services;

use App\Enums\ImageScope;
use App\Models\Group;
use App\Models\Image;
use App\Models\User;
use App\Services\AbstractDomainService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class UserService extends AbstractDomainService
{
    protected bool $forgetsMasterCacheOnWrite = true;

    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    protected function getSelectColumns(): array
    {
        return ['id', 'name', 'language', 'avatar_seed', 'avatar_image_id'];
    }

    protected function getResourceName(): string
    {
        return __('api.attributes.user');
    }

    protected function getGroupRelation(Group $group): BelongsToMany
    {
        return $group->users();
    }

    protected function getWithColumns(): array
    {
        return ['avatarImage'];
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
    public function formatUserAvatar(User $user): array
    {
        return [
            'seed' => $user->avatar_seed,
            'image' => $this->imageService->formatImage($user->avatarImage),
        ];
    }

    /**
     * プロフィールを更新
     *
     * @param User $user 更新対象のユーザー
     * @param array $data バリデーション済みの更新データ
     */
    public function updateProfile(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {
            $updateData = [];

            // アバター画像を事前にロード
            $user->load(['avatarImage']);

            if (array_key_exists('name', $data)) {
                $updateData['name'] = $data['name'];
            }

            // キー省略時は null 扱いで紐づけ解除する
            $avatarImageId = $data['avatar_image_id'] ?? null;
            $currentImageId = $user->avatarImage?->id;

            // 新しい画像IDが指定されている場合、先に存在確認とスコープ検証を行う
            // これにより、存在しない画像やユーザースコープ外の画像の場合は古い画像を削除する前にエラーになる
            if ($avatarImageId !== null) {
                $this->imageService->findImagesByIds(
                    [$avatarImageId],
                    user: $user,
                    scope: ImageScope::USER
                );
            }

            $updateData['avatar_image_id'] = $avatarImageId;
            // 注: 画像の紐づけ解除は users.avatar_image_id を null に更新することで行う（キー省略時も null 扱い）
            // imagesテーブルからは削除しない

            if (!empty($updateData)) {
                // 更新処理
                // 注: Userのアバター画像は image_mappings テーブルを使用せず、
                // users.avatar_image_id カラムで直接 images テーブルと紐づいているため、
                // 単に avatar_image_id を更新するだけでOK（detach() は不要）
                $user->update($updateData);
            }
        });

        foreach ($user->groups()->get() as $group) {
            MasterService::forgetGroupCache($group);
        }
    }

    /**
     * アカウントを削除する
     * トランザクション内で Sanctum トークン削除 → ユーザー削除（CASCADE）→ グループの refreshGroupSize
     *
     * @param User $user 削除対象のユーザー
     */
    public function deleteAccount(User $user): void
    {
        $groups = $user->groups()->get();

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $group = $user->groups()->first();
            $this->imageService->deleteImagesByUser($user);
            $user->delete();
            if ($group) {
                $group->refreshGroupSize();
            }
        });

        foreach ($groups as $group) {
            MasterService::forgetGroupCache($group);
        }
    }
}
