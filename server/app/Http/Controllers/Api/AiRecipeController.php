<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\AiRecipeParseRequest;
use App\Interfaces\AiRecipeParserInterface;
use App\Services\AiUsageService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AiRecipeController extends ApiController
{
    public function __construct(
        private readonly AiRecipeParserInterface $recipeParser,
        private readonly AiUsageService $aiUsageService,
    ) {}

    /**
     * @OA\Post(
     *     path="/ai/recipes/parse",
     *     summary="画像からレシピ情報をAI解析",
     *     tags={"AI"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"image"},
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     format="binary",
     *                     description="解析対象のレシピ画像（jpeg/png/webp、10MB以下）"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, ref="#/components/responses/AiRecipeParseSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors"),
     *     @OA\Response(response=429, ref="#/components/responses/AiUsageLimitExceeded")
     * )
     */
    public function parse(AiRecipeParseRequest $request): JsonResponse
    {
        $operation = __('operations.ai.recipe.parse');
        $failedMessage = __('api.ai.recipe.parse_failed');

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $group = $this->getUserGroup($request);
                $fromPack = $this->aiUsageService->consumeUsage($group);

                try {
                    $image = $request->file('image');
                    $base64Image = base64_encode((string) file_get_contents($image->getRealPath()));

                    $parsedRecipe = $this->recipeParser->parseImage($base64Image);
                    $message = __('api.ai.recipe.parsed');

                    return $this->showResponse($parsedRecipe->toArray(), $message);
                } catch (Throwable $e) {
                    $this->aiUsageService->refundUsage($group, $fromPack);
                    throw $e;
                }
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
