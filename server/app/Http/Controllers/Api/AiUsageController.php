<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\AiUsageShowRequest;
use App\Services\AiUsageService;
use Illuminate\Http\JsonResponse;

class AiUsageController extends ApiController
{
    public function __construct(
        private readonly AiUsageService $aiUsageService,
    ) {}

    /**
     * @OA\Get(
     *     path="/ai/usage",
     *     summary="AI利用状況を取得",
     *     tags={"AI"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/AiUsageShowSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors")
     * )
     */
    public function show(AiUsageShowRequest $request): JsonResponse
    {
        $operation = __('operations.ai.usage.show');
        $failedMessage = __('api.get_failed', ['attribute' => __('api.attributes.ai_usage')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $group = $this->getUserGroup($request);
                $status = $this->aiUsageService->getUsageStatus($group);
                $message = __('api.retrieved', ['attribute' => __('api.attributes.ai_usage')]);

                return $this->showResponse($status, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
