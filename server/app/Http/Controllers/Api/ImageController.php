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
            $request->validate([
                'images' => 'required',
                'images.*' => $this->imageService->getValidationRules(),
                'directory' => 'nullable|string|max:255'
            ], [
                'images.required' => '画像ファイルを選択してください。',
                'directory.max' => 'ディレクトリ名は255文字以内で入力してください。'
            ]);

            // 画像ファイルの処理（単一ファイルまたは配列に対応）
            $imageFiles = $request->file('images');
            if (!is_array($imageFiles)) {
                $imageFiles = [$imageFiles];
            }

            // 配列の長さチェック
            if (empty($imageFiles) || count($imageFiles) > 20) {
                return $this->validationErrorResponse(
                    new ValidationException(
                        validator(['images' => 'max:20'], ['images.max' => '画像は最大20枚までアップロードできます。']),
                        response()->json(['message' => '画像は最大20枚までアップロードできます。'], 422)
                    )
                );
            }

            // ディレクトリの設定
            $directory = $request->input('directory', 'general');
            $uploadPath = "$group->id/$directory";

            $uploadedImages = [];

            // 画像を並列処理（単一ファイルまたは複数ファイルに対応）
            foreach ($imageFiles as $file) {
                $image = $this->imageService->uploadAndSaveImage($file, $uploadPath);
                $uploadedImages[] = [
                    'id' => $image->id,
                    'src' => $image->src,
                    'width' => $image->width,
                    'height' => $image->height,
                ];
            }

            return $this->successResponse($uploadedImages, '画像をアップロードしました。');
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
