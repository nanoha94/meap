<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BaseApiRequest;
use App\Models\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="meap api document",
 *      description="meap api document",
 * )
 * @OA\Schema(
 *     schema="BaseApiResponse",
 *     description="成功（success, message, data: null）",
 *     required={"success", "message", "data"},
 *     @OA\Property(property="success", type="boolean", example=true, description="成功フラグ"),
 *     @OA\Property(property="message", type="string", example="操作が完了しました。", description="結果メッセージ"),
 *     @OA\Property(property="data", type="object", nullable=true, example=null, description="store/update/destroy 等では常に null")
 * )
 * @OA\Schema(
 *     schema="BaseApiIndexResponse",
 *     description="成功（success, message, data: 配列, total）",
 *     required={"success", "message", "data", "total"},
 *     @OA\Property(property="success", type="boolean", example=true, description="成功フラグ"),
 *     @OA\Property(property="message", type="string", example="一覧を取得しました。", description="結果メッセージ"),
 *     @OA\Property(property="data", type="array", @OA\Items(type="object"), description="一覧データ"),
 *     @OA\Property(property="total", type="integer", example=10, description="総件数")
 * )
 * @OA\Tag(
 *     name="Authentication",
 *     description="認証関連のAPI　※swagger上でAPIを実行するには、まずはログインが必要"
 * )
 * @OA\Tag(
 *     name="Users",
 *     description="ユーザー関連のAPI"
 * )
 * @OA\Tag(
 *     name="Invitations",
 *     description="招待関連のAPI"
 * )
 * @OA\Tag(
 *     name="Recipes",
 *     description="料理関連のAPI"
 * )
 * @OA\Tag(
 *     name="Ingredients",
 *     description="食材関連のAPI"
 * )
 * @OA\Tag(
 *     name="MealPlans",
 *     description="献立関連のAPI"
 * )
 * @OA\Tag(
 *     name="Shopping",
 *     description="買い物関連のAPI"
 * )
 * @OA\Tag(
 *     name="Images",
 *     description="画像関連のAPI"
 * )
 * @OA\Tag(
 *     name="AI",
 *     description="AI機能関連のAPI"
 * )
 * @OA\Tag(
 *     name="Billing",
 *     description="課金・サブスクリプション関連のAPI"
 * )
 */

abstract class ApiController extends Controller
{
    /**
     * ユーザーのグループを取得
     */
    protected function getUserGroup(Request|FormRequest|BaseApiRequest $request): Group
    {
        try {
            return $request->user()->groups()->sole();
        } catch (ModelNotFoundException) {
            throw new HttpException(
                HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                __('api.general.not_belong_to_any_group')
            );
        } catch (MultipleRecordsFoundException) {
            throw new HttpException(
                HttpStatusCode::UNPROCESSABLE_ENTITY->value,
                __('api.general.belong_to_multiple_groups')
            );
        }
    }
}
