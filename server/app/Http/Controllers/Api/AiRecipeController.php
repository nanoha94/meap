<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\AiRecipeParseRequest;
use App\Http\Requests\Api\AiRecipeParseUrlRequest;
use App\Interfaces\AiRecipeParserInterface;
use App\Services\Ai\AiRecipeService;
use App\Services\AiUsageService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AiRecipeController extends ApiController
{
    public function __construct(
        private readonly AiRecipeParserInterface $recipeParser,
        private readonly AiUsageService $aiUsageService,
        private readonly AiRecipeService $aiRecipeService,
    ) {}

    /**
     * @OA\Post(
     *     path="/ai/recipes/parse-img",
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
    public function parseImage(AiRecipeParseRequest $request): JsonResponse
    {
        $operation = __('operations.ai.recipe.parse_img');
        $failedMessage = __('api.ai.recipe.parse_img_failed');

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $group = $this->getUserGroup($request);
                $fromPack = $this->aiUsageService->consumeUsage($group);

                try {
                    $image = $request->file('image');
                    $base64Image = base64_encode((string) file_get_contents($image->getRealPath()));
                    $unitNames = $group->ingredientUnits()
                        ->orderBy('order')
                        ->pluck('name')
                        ->all();

                    $parsedRecipe = $this->recipeParser->parseImage($base64Image, $unitNames);
                    $normalizedRecipe = $this->aiRecipeService->normalizeParsedRecipe($parsedRecipe, $group);
                    $message = __('api.ai.recipe.parsed_img');

                    return $this->showResponse($normalizedRecipe->toArray(), $message);
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

    /**
     * @OA\Post(
     *     path="/ai/recipes/parse-url",
     *     summary="URLからレシピ情報をAI解析",
     *     tags={"AI"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"url"},
     *             @OA\Property(
     *                 property="url",
     *                 type="string",
     *                 format="uri",
     *                 description="解析対象のレシピURL（2048文字以下）",
     *                 example="https://example.com/recipe/123"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, ref="#/components/responses/AiRecipeParseSuccess"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors"),
     *     @OA\Response(response=429, ref="#/components/responses/AiUsageLimitExceeded")
     * )
     */
    public function parseUrl(AiRecipeParseUrlRequest $request): JsonResponse
    {
        $operation = __('operations.ai.recipe.parse_url');
        $failedMessage = __('api.ai.recipe.parse_url_failed');

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $group = $this->getUserGroup($request);
                $fromPack = $this->aiUsageService->consumeUsage($group);

                try {
                    $url = $request->validated('url');
                    $unitNames = $group->ingredientUnits()
                        ->orderBy('order')
                        ->pluck('name')
                        ->all();

                    $parsedRecipe = $this->recipeParser->parseUrl($url, $unitNames);
                    $normalizedRecipe = $this->aiRecipeService->normalizeParsedRecipe($parsedRecipe, $group);
                    $message = __('api.ai.recipe.parsed_url');

                    return $this->showResponse($normalizedRecipe->toArray(), $message);
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
