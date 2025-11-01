<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Models\Group;
use App\Models\Image;
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
     * 指定されたIDの画像を取得し、グループスコープで検証
     * 
     * @param array $imageIds 取得する画像IDの配列
     * @param \App\Models\Group $group ユーザーの所属グループ（検証用）
     * @return \Illuminate\Support\Collection 検証済みの画像コレクション
     * @throws HttpException 画像が見つからない、またはグループに属していない場合
     */
    public function findImagesByIds(array $imageIds, Group $group): Collection
    {
        if (empty($imageIds)) {
            return collect();
        }

        $images = Image::whereIn('id', $imageIds)->get();
        $groupIdPattern = "/images\\/{$group->id}\\//";

        // すべての画像が存在し、かつグループに属していることを確認
        $validImages = collect();
        foreach ($imageIds as $imageId) {
            $image = $images->firstWhere('id', $imageId);

            if (!$image) {
                throw new HttpException(
                    HttpStatusCode::NOT_FOUND->value,
                    __('api.not_found', ['attribute' => __('api.attributes.image')])
                );
            }

            if (!preg_match($groupIdPattern, $image->src)) {
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
     * 画像を一括削除
     * 
     * @param array $imageIds 削除する画像IDの配列
     * @param \App\Models\Group $group ユーザーの所属グループ（安全性チェック用）
     * @return int 削除された画像の数
     */
    public function deleteImages(array $imageIds, Group $group): int
    {
        if (empty($imageIds)) {
            return 0;
        }

        // 指定されたIDの画像を取得し、グループIDがパスに含まれているかチェック
        $images = Image::whereIn('id', $imageIds)->get();
        $groupIdPattern = "/images\\/{$group->id}\\//";

        // 削除対象の画像を抽出（グループチェック）
        $imagesToDelete = [];
        foreach ($images as $image) {
            if (!preg_match($groupIdPattern, $image->src)) {
                $this->logWarning(__METHOD__,  __('operations.image.bulk_destroy'), __('api.image.group_mismatch'), [
                    'image_id' => $image->id,
                    'image_src' => $image->src,
                    'expected_group_id' => $group->id
                ]);
                continue;
            }
            $imagesToDelete[] = $image;
        }

        if (empty($imagesToDelete)) {
            return 0;
        }

        // トランザクション内でDB削除を実行
        $deletedImages = DB::transaction(function () use ($imagesToDelete) {
            $deleted = [];
            foreach ($imagesToDelete as $image) {
                // データベースから削除（image_mappingsも外部キー制約でカスケード削除される）
                $image->delete();
                $deleted[] = $image;
            }
            return $deleted;
        });

        // トランザクションコミット後、ファイルを削除
        foreach ($deletedImages as $image) {
            if (!$this->deleteImageFile($image->src)) {
                $this->logWarning(__METHOD__,  __('operations.image.bulk_destroy'), __('api.image.deletion_failed'), [
                    'image_id' => $image->id,
                    'image_src' => $image->src
                ]);
                throw new HttpException(HttpStatusCode::INTERNAL_SERVER_ERROR->value, __('api.image.deletion_failed'));
            }
        }

        return count($deletedImages);
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
