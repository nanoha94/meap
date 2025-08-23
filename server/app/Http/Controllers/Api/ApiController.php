<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="meap api document",
 *      description="meap api document",
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
 */

abstract class ApiController extends Controller
{
    /**
     * ユーザーのグループを取得
     */
    protected function getUserGroup(Request $request)
    {
        return $request->user()->group;
    }
}
