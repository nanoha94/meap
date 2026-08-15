<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\ImageGroupBulkUploadRequest;
use App\Http\Requests\Api\ImageUserUploadRequest;
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
     *     path="/images/groups/upload-bulk",
     *     summary="グループ画像をアップロード（単一または複数対応）",
     *     tags={"Images"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/ImageGroupBulkUploadRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ImageUploadBulkSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function bulkUploadForGroup(ImageGroupBulkUploadRequest $request): JsonResponse
    {
        $operation = __('operations.image.bulk_upload');
        $failedMessage = __('api.image.upload_failed');

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $group = $this->getUserGroup($request);

                // 画像ファイルを取得
                $imageFiles = $this->imageService->getValidImageFiles($request, 20);

                $uploadPath = 'groups/' . $group->id;

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
     * @OA\Post(
     *     path="/images/users/upload",
     *     summary="ユーザー画像をアップロード",
     *     tags={"Images"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/requestBodies/ImageUserUploadRequest"),
     *     @OA\Response(response=200, ref="#/components/responses/ImageUploadSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function uploadForUser(ImageUserUploadRequest $request): JsonResponse
    {
        $operation = __('operations.image.upload');
        $failedMessage = __('api.image.upload_failed');

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $user = $request->user();
                $file = $request->file('image');
                $image = $this->imageService->uploadAndSaveImage($file, 'users/' . $user->id);
                $data = $this->imageService->formatImage($image);
                $message = __('api.image.uploaded');

                return $this->showResponse($data, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
