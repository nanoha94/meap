<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\ImageBulkUploadRequest;
use App\Http\Requests\Api\ImageBulkDestroyRequest;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;

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
     *     @OA\RequestBody(ref="#/components/requestBodies/ImageBulkUploadRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ImageUploadBulkSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function bulkUpload(ImageBulkUploadRequest $request): JsonResponse
    {
        $operation = __('operations.image.bulk_upload');
        $failedMessage = __('api.image.upload_failed');

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $group = $this->getUserGroup($request);
                $validated = $request->validated();

                // 画像ファイルを取得
                $imageFiles = $this->imageService->getValidImageFiles($request, 20);

                // upload_path 指定時はそのパスを使用、未指定時は groups/{group_id} 配下に保存
                $uploadPath = $validated['upload_path'] ?? 'groups/' . $group->id;

                // 画像をアップロード
                $uploadedImages = collect($imageFiles)
                    ->map(fn($file) => $this->imageService->uploadAndSaveImage($file, $uploadPath))
                    ->pipe(fn($images) => $this->imageService->formatBulkImageUploadResponse($images));

                $total = count($uploadedImages);
                $message = __('api.image.bulk_uploaded', ['count' => $total]);

                return $this->indexResponse($uploadedImages, $total, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

    /**
     * @OA\Delete(
     *     path="/images/bulk",
     *     summary="画像を一括削除",
     *     tags={"Images"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/ImageBulkDestroyRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ImageDeleteBulkSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function bulkDestroy(ImageBulkDestroyRequest $request): JsonResponse
    {
        $operation = __('operations.image.bulk_destroy');
        $failedMessage = __('api.image.bulk_deletion_failed');

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $group = $this->getUserGroup($request);
                $validated = $request->validated();
                $imageIds = $validated['ids'];
                $relatedId = $validated['related_id'];
                $deletedCount = $this->imageService->deleteImages($imageIds, $relatedId, $group);
                $message = __('api.image.bulk_deleted', ['count' => $deletedCount]);

                return $this->deletedResponse($message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
