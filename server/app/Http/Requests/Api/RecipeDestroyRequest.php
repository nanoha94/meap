<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiRequest;
use App\Models\Recipe;

class RecipeDestroyRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // ユーザーがグループに所属しているかの基本チェック
        if (!parent::authorize()) {
            return false;
        }

        // ルートパラメータからレシピIDを取得
        $recipeId = $this->route('recipe');

        // レシピを検索
        $recipe = Recipe::find($recipeId);

        // レシピが存在しない場合、または自分のグループのレシピではない場合は、
        // 既存の挙動（404エラー）を優先させるため認可をパスさせる
        $userGroup = $this->user()->groups()->sole();
        if (!$recipe || $recipe->group_id !== $userGroup->id) {
            return true;
        }

        // 同じグループ内の他人のレシピを削除しようとした場合のみ403を返す
        return $recipe->owner_user_id === $this->user()->id;
    }

    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.recipe.destroy');
    }
}
