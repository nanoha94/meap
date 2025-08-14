<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Services\ImageService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ImageController extends ApiController
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * @OA\Post(
     *     path="/images/upload-bulk",
     *     summary="画像をアップロード（単一または複数対応）",
     *     tags={"Images"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/ImageUploadBulkRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ImageUploadBulkSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function uploadBulk(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            // バリデーション
            $validationRules = ['images.0' => 'required|file|' . implode('|', $this->imageService->getValidationRules())];

            // 2枚目から20枚目までを任意フィールドとして追加
            for ($i = 1; $i < 20; $i++) {
                $validationRules["images.{$i}"] = 'nullable|file|' . implode('|', $this->imageService->getValidationRules());
            }

            $validationRules['directory'] = 'nullable|string|max:255';

            $request->validate($validationRules, [
                'images.0.required' => '1枚目の画像ファイルを選択してください。',
                'images.0.file' => '1枚目は有効なファイルを選択してください。',
                'images.*.file' => '有効なファイルを選択してください。',
                'directory.max' => 'ディレクトリ名は255文字以内で入力してください。'
            ]);

            // 画像ファイルを取得
            $imageFiles = collect(range(0, 19))
                ->map(fn($i) => $request->file("images.{$i}"))
                ->filter(fn($file) => $file && $file->isValid())
                ->values()
                ->toArray();

            // ディレクトリの設定
            $directory = $request->input('directory', 'general');
            $uploadPath = "$group->id/$directory";

            // 画像をアップロード
            $uploadedImages = collect($imageFiles)
                ->map(fn($file) => $this->imageService->uploadAndSaveImage($file, $uploadPath))
                ->map(fn($image) => [
                    'id' => $image->id,
                    'src' => $image->src,
                    'width' => $image->width,
                    'height' => $image->height,
                ])
                ->toArray();

            return $this->successResponse($uploadedImages, count($uploadedImages) . '枚の画像をアップロードしました。');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            Log::error('Bulk image upload failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleException($e, $request, '画像のアップロードに失敗しました。');
        }
    }

    /**
     * @OA\Delete(
     *     path="/images/bulk",
     *     summary="画像を一括削除",
     *     tags={"Images"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/ImageDeleteBulkRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ImageDeleteBulkSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function deleteBulk(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            $request->validate([
                'image_ids' => 'required|array',
                'image_ids.*' => 'required|string|exists:images,id'
            ], [
                'image_ids.required' => '画像IDが必要です。',
                'image_ids.*.exists' => '指定された画像IDが存在しません。'
            ]);

            $imageIds = $request->input('image_ids');
            $deletedCount = $this->imageService->deleteImages($imageIds);

            return $this->successResponse(null, $deletedCount . '件の画像を削除しました。');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            Log::error('Bulk image delete failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleException($e, $request, '件の画像の削除に失敗しました。');
        }
    }
}
