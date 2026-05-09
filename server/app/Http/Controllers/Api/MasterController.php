<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\MasterRequest;
use App\Services\MasterService;
use Illuminate\Http\JsonResponse;

class MasterController extends ApiController
{
    private MasterService $masterService;

    public function __construct(MasterService $masterService)
    {
        $this->masterService = $masterService;
    }

    /**
     * @OA\Get(
     *     path="/master",
     *     summary="マスターデータを取得",
     *     tags={"Master"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/MasterSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function __invoke(MasterRequest $request): JsonResponse
    {
        $operation = __('operations.master.index');
        $failedMessage = __('api.get_failed', ['attribute' => __('api.attributes.master')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $group = $this->getUserGroup($request);
                $res = $this->masterService->index($group);
                $message = __('api.retrieved', ['attribute' => __('api.attributes.master')]);

                return $this->successResponse($res, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
