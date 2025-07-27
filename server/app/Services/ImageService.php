<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImageService
{
    /**
     * 画像をアップロードして情報を返す（重複チェック付き）
     *
     * @param UploadedFile $file
     * @param string $directory アップロード先ディレクトリ
     * @param string|null $existingImageUrl 既存の画像URL（更新時）
     * @return array
     */
    public function uploadImage(
        UploadedFile $file,
        string $directory,
        ?string $existingImageUrl = null
    ): array {
        // 既存画像との重複チェック
        // アップロードするファイルのサイズが0の場合は、更新しないので、既存の画像を返す
        if ($file->getSize() === 0 || ($existingImageUrl && $this->isSameImage($file, $existingImageUrl))) {
            // 同じ画像の場合、既存の情報を返す
            $imageInfo = $this->getExistingImageInfo($existingImageUrl);
            return [
                'url' => $existingImageUrl,
                'width' => $imageInfo['width'],
                'height' => $imageInfo['height'],
                'is_same_image' => true // 同じ画像であることを示すフラグ
            ];
        }

        // 既存画像を削除（新しい画像の場合のみ）
        if ($existingImageUrl) {
            $this->deleteImage($existingImageUrl);
        }

        // 画像サイズを取得
        $imageInfo = getimagesize($file->getRealPath());
        if (!$imageInfo) {
            throw new \Exception('画像情報を取得できませんでした。');
        }

        // ファイルを保存
        $path = $file->store($directory, 'public');
        $url = env('APP_URL') . '/storage/' . $path;

        return [
            'url' => $url,
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'is_same_image' => false
        ];
    }

    /**
     * 画像ファイルを削除
     *
     * @param string $imageUrl
     * @return void
     */
    public function deleteImage(string $imageUrl): void
    {
        if (empty($imageUrl)) {
            return;
        }

        try {
            $path = str_replace('/storage/', '', $imageUrl);
            Storage::disk('public')->delete($path);
        } catch (\Exception $e) {
            Log::warning('画像削除に失敗しました: ' . $e->getMessage(), [
                'image_url' => $imageUrl
            ]);
        }
    }

    /**
     * 画像のバリデーションルールを取得
     *
     * @param int $maxSizeKb 最大ファイルサイズ（KB）
     * @return array
     */
    public function getValidationRules(int $maxSizeKb = 2048): array
    {
        return [
            'image',
            'mimes:jpeg,png,jpg,gif,webp',
            "max:{$maxSizeKb}"
        ];
    }

    /**
     * 新しい画像と既存の画像が同じかどうかをチェック
     *
     * @param UploadedFile $newFile
     * @param string $existingImageUrl
     * @return bool
     */
    public function isSameImage(UploadedFile $newFile, string $existingImageUrl): bool
    {
        try {
            // 既存ファイルのパスを取得
            $existingPath = $this->getStoragePath($existingImageUrl);
            $existingFullPath = Storage::disk('public')->path($existingPath);

            // 既存ファイルが存在するかチェック
            if (!Storage::disk('public')->exists($existingPath)) {
                return false;
            }

            // ファイルサイズの比較
            $newFileSize = $newFile->getSize();
            $existingFileSize = Storage::disk('public')->size($existingPath);

            if ($newFileSize !== $existingFileSize) {
                return false;
            }

            // ファイルハッシュで比較
            $newFileHash = hash_file('sha256', $newFile->getRealPath());
            $existingFileHash = hash_file('sha256', $existingFullPath);

            return $newFileHash === $existingFileHash;
        } catch (\Exception $e) {
            Log::warning('画像比較に失敗しました: ' . $e->getMessage(), [
                'existing_url' => $existingImageUrl
            ]);
            return false;
        }
    }

    /**
     * 既存画像の情報を取得
     *
     * @param string $imageUrl
     * @return array
     */
    public function getExistingImageInfo(string $imageUrl): array
    {
        try {
            $existingPath = $this->getStoragePath($imageUrl);
            $existingFullPath = Storage::disk('public')->path($existingPath);

            if (!Storage::disk('public')->exists($existingPath)) {
                throw new \Exception('既存画像が見つかりません。');
            }

            $imageInfo = getimagesize($existingFullPath);
            if (!$imageInfo) {
                throw new \Exception('既存画像の情報を取得できませんでした。');
            }

            return [
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
            ];
        } catch (\Exception $e) {
            Log::warning('既存画像情報の取得に失敗しました: ' . $e->getMessage(), [
                'image_url' => $imageUrl
            ]);

            // デフォルト値を返す
            return [
                'width' => 300,
                'height' => 200,
            ];
        }
    }

    /**
     * 画像URLからストレージパスを取得
     *
     * @param string $imageUrl
     * @return string
     */
    private function getStoragePath(string $imageUrl): string
    {
        return str_replace('/storage/', '', $imageUrl);
    }
}
