<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Enums\ImageScope;
use App\Models\Group;
use App\Models\Image;
use App\Models\User;
use App\Traits\LoggingTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ImageService
{
    use LoggingTrait;
    /**
     * 画像のバリデーションルールを取得
     */
    public function getValidationRules(): array
    {
        return [
            'image',
            'mimes:jpeg,png,jpg,gif,webp',
            'max:10240', // 10MB
        ];
    }

    /**
     * 画像をアップロードして保存
     */
    public function uploadAndSaveImage($file, $uploadPath): Image
    {
        $fileName = $this->generateFileName($file);
        $fullPath = "images/{$uploadPath}/{$fileName}";

        // ファイルをアップロード
        Storage::disk('public')->put($fullPath, file_get_contents($file));

        // 画像情報を取得
        $imageInfo = getimagesize($file->getPathname());
        $width = $imageInfo[0] ?? null;
        $height = $imageInfo[1] ?? null;


        // データベースに保存
        return DB::transaction(function () use ($fullPath, $width, $height) {
            return Image::create([
                'src' => Storage::disk('public')->url($fullPath),
                'width' => $width,
                'height' => $height,
            ]);
        });
    }


    /**
     * 指定されたIDの画像を取得し、スコープで検証
     * 
     * @param array $imageIds 取得する画像IDの配列
     * @param \App\Models\Group|null $group グループスコープ検証時に使用するグループ（scope=ImageScope::GROUPの場合に必須）
     * @param \App\Models\User|null $user ユーザースコープ検証時に使用するユーザー（scope=ImageScope::USERの場合に使用）
     * @param \App\Enums\ImageScope $scope 検証スコープ。デフォルトは ImageScope::GROUP
     * @return \Illuminate\Support\Collection 検証済みの画像コレクション
     * @throws HttpException 画像が見つからない、または指定されたスコープに属していない場合
     */
    public function findImagesByIds(
        array $imageIds,
        ?Group $group = null,
        ?User $user = null,
        ImageScope $scope = ImageScope::GROUP
    ): Collection {
        if (empty($imageIds)) {
            return collect();
        }

        $images = Image::whereIn('id', $imageIds)->get();

        // スコープに応じてパターンを決定
        $pattern = $this->getScopePattern($scope, $group, $user);

        // すべての画像が存在し、かつ指定されたスコープに属していることを確認
        $validImages = collect();
        foreach ($imageIds as $imageId) {
            $image = $images->firstWhere('id', $imageId);

            if (!$image) {
                throw new HttpException(
                    HttpStatusCode::NOT_FOUND->value,
                    __('api.not_found', ['attribute' => __('api.attributes.image')])
                );
            }

            if (!preg_match($pattern, $image->src)) {
                throw new HttpException(
                    HttpStatusCode::NOT_FOUND->value,
                    __('api.not_found', ['attribute' => __('api.attributes.image')])
                );
            }

            $validImages->push($image);
        }

        return $validImages;
    }

    /**
     * スコープに応じたパスパターンを取得
     * 
     * @param \App\Enums\ImageScope $scope 検証スコープ
     * @param \App\Models\Group|null $group グループ（groupスコープ時に使用）
     * @param \App\Models\User|null $user ユーザー（userスコープ時に使用）
     * @return string 正規表現パターン
     * @throws HttpException 必須パラメータが不足している場合
     */
    private function getScopePattern(ImageScope $scope, ?Group $group, ?User $user): string
    {
        return match ($scope) {
            ImageScope::USER => $this->getUserScopePattern($user),
            ImageScope::GROUP => $this->getGroupScopePattern($group),
        };
    }

    /**
     * ユーザースコープのパスパターンを取得
     * 
     * @param \App\Models\User|null $user ユーザー
     * @return string 正規表現パターン
     * @throws HttpException ユーザーが指定されていない場合
     */
    private function getUserScopePattern(?User $user): string
    {
        // ユーザースコープ: images/users/{user_id}/ のみ許可
        if ($user === null) {
            throw new HttpException(
                HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'User is required for user scope validation'
            );
        }
        $escapedUserId = preg_quote($user->id, '/');
        return "/images\\/users\\/{$escapedUserId}\\//";
    }

    /**
     * グループスコープのパスパターンを取得
     * 
     * @param \App\Models\Group|null $group グループ
     * @return string 正規表現パターン
     * @throws HttpException グループが指定されていない場合
     */
    private function getGroupScopePattern(?Group $group): string
    {
        if ($group === null) {
            throw new HttpException(
                HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'Group is required for group scope validation'
            );
        }

        // グループスコープ: images/groups/{group_id}/ のみ許可
        $escapedGroupId = preg_quote($group->id, '/');
        return "/images\\/groups\\/{$escapedGroupId}\\//";
    }

    /**
     * 画像を一括削除
     * 
     * 指定されたrelatedIdとの紐づけを解除します。
     * imagesテーブルからは削除せず、紐づけ解除のみを行います。
     * 
     * @param array $imageIds 削除する画像IDの配列
     * @param string $relatedId 紐づけを解除するエンティティのID（必須）
     * @param \App\Models\Group $group ユーザーの所属グループ（安全性チェック用）
     * @return int 紐づけ解除された画像の数
     */
    public function deleteImages(array $imageIds, string $relatedId, Group $group): int
    {
        if (empty($imageIds)) {
            return 0;
        }

        // 指定されたIDの画像を取得し、グループIDがパスに含まれているかチェック
        $images = Image::whereIn('id', $imageIds)->get();
        $escapedGroupId = preg_quote($group->id, '/');
        // 旧形式(images/{group_id}/...)と現形式(images/groups/{group_id}/...)の両方を許可
        $groupIdPattern = "/images\\/(groups\\/)?{$escapedGroupId}\\//";

        // 削除対象の画像を抽出（グループチェック）
        $imagesToProcess = [];
        foreach ($images as $image) {
            if (!preg_match($groupIdPattern, $image->src)) {
                $this->logWarning(__METHOD__,  __('operations.image.bulk_destroy'), __('api.image.group_mismatch'), [
                    'image_id' => $image->id,
                    'image_src' => $image->src,
                    'expected_group_id' => $group->id
                ]);
                continue;
            }
            $imagesToProcess[] = $image;
        }

        if (empty($imagesToProcess)) {
            return 0;
        }

        // トランザクション内で紐づけ解除を実行
        $unlinkedCount = DB::transaction(function () use ($imagesToProcess, $relatedId, $group) {
            $count = 0;
            foreach ($imagesToProcess as $image) {
                // 指定された紐づけを解除（主キー: image_id, related_id, group_id）
                $deletedMappingCount = DB::table('image_mappings')
                    ->where('image_id', $image->id)
                    ->where('group_id', $group->id)
                    ->where('related_id', $relatedId)
                    ->delete();

                if ($deletedMappingCount > 0) {
                    $count++;
                }
            }
            return $count;
        });

        return $unlinkedCount;
    }

    /**
     * 画像情報をフォーマット
     */
    public function formatImage($image): ?array
    {
        if (!$image) {
            return null;
        }

        return [
            'id' => $image->id,
            'src' => $image->src,
            'width' => $image->width,
            'height' => $image->height,
        ];
    }

    /**
     * 画像の一括アップロードレスポンスをフォーマット
     */
    public function formatBulkImageUploadResponse($images): array
    {
        return collect($images)->map(fn($image) => $this->formatImage($image))->toArray();
    }


    /**
     * 画像ファイルの配列を取得（無効なファイルを除外）
     */
    public function getValidImageFiles(Request $request, $maxImages = 20): array
    {
        return collect(range(0, $maxImages - 1))
            ->map(fn($i) => $request->file("images.{$i}"))
            ->filter(fn($file) => $file && $file->isValid())
            ->values()
            ->toArray();
    }

    /**
     * ファイル名を生成
     */
    private function generateFileName($file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->timestamp;
        $random = uniqid();

        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * パスからグループIDを抽出
     */
    private function extractGroupIdFromPath($uploadPath): ?string
    {
        $parts = explode('/', $uploadPath);
        return $parts[0] ?? null;
    }

    /**
     * 画像ファイルを削除
     */
    private function deleteImageFile($imageUrl): bool
    {
        try {
            $path = str_replace('/storage/', '', parse_url($imageUrl, PHP_URL_PATH));
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
