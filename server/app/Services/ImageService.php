<?php

namespace App\Services;

use App\Models\Image;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageService
{
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
        return Image::create([
            'src' => Storage::disk('public')->url($fullPath),
            'width' => $width,
            'height' => $height,
            'group_id' => $this->extractGroupIdFromPath($uploadPath),
        ]);
    }

    /**
     * 画像を一括削除
     */
    public function deleteImages(array $imageIds): int
    {
        $images = Image::whereIn('id', $imageIds)->get();
        $deletedCount = 0;

        foreach ($images as $image) {
            try {
                // ファイルを削除
                $this->deleteImageFile($image->src);

                // データベースから削除
                $image->delete();
                $deletedCount++;
            } catch (Exception $e) {
                // ログ出力は呼び出し元で行う
                continue;
            }
        }

        return $deletedCount;
    }

    /**
     * 画像情報をフォーマット
     */
    public function formatImage($image): array
    {
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
     * 画像アップロード用のバリデーションルールを生成
     */
    public function generateImageValidationRules($maxImages = 20, $validationRules = []): array
    {
        $rules = ['images.0' => 'required|file|' . implode('|', $validationRules)];

        // 2枚目から指定枚数までを任意フィールドとして追加
        for ($i = 1; $i < $maxImages; $i++) {
            $rules["images.{$i}"] = 'nullable|file|' . implode('|', $validationRules);
        }

        $rules['directory'] = 'nullable|string|max:255';
        return $rules;
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
