<?php

namespace App\Http\Requests\Api;

use App\Enums\HttpStatusCode;
use App\Http\Requests\Api\BaseApiRequest;
use App\Models\InvitationToken;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvitationJoinRequest extends BaseApiRequest
{
    protected ?InvitationToken $invitationToken = null;

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'isDelete' => ['boolean'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $token = $this->route('token');
        $user = $this->user();

        // まず全トークンからハッシュチェック（有効期限無視）
        $foundToken = InvitationToken::all()->first(function ($record) use ($token) {
            return Hash::check($token, $record->token);
        });

        // 1. トークンが存在しない → 404 Not Found
        if (!$foundToken) {
            throw new HttpException(
                HttpStatusCode::NOT_FOUND->value,
                __('api.invitation.token_not_found')
            );
        }

        // 2. 有効期限切れ → 410 Gone
        if ($foundToken->expires_at < now()) {
            throw new HttpException(
                HttpStatusCode::GONE->value,
                __('api.invitation.token_expired')
            );
        }

        $this->invitationToken = $foundToken;

        // 3. 自己招待チェック → 403 Forbidden
        if ($this->invitationToken->inviter_user_id === $user->id) {
            throw new HttpException(
                HttpStatusCode::FORBIDDEN->value,
                __('api.invitation.self_invitation_error')
            );
        }

        // 4. すでに同じグループにいるかチェック → 409 Conflict
        $currentGroup = $user->groups()->first();
        if ($currentGroup->id === $this->invitationToken->inviter->groups()->first()->id) {
            throw new HttpException(
                HttpStatusCode::CONFLICT->value,
                __('api.invitation.already_in_group')
            );
        }

        // 5. isDeleteがfalseの場合の追加チェック
        // 初回joinリクエストのときは、isDeleteがfalseの想定
        // その後、フロント側で削除確認を行い、isDeleteがtrueで再度リクエストされる想定
        if (!$this->boolean('isDelete')) {
            // 他のグループに所属しているかチェック
            if ($currentGroup->group_size > 1) {
                throw new HttpException(
                    HttpStatusCode::CONFLICT->value,
                    __('api.invitation.already_in_another_group'),
                    null,
                    ['X-Error-Type' => 'already_in_another_group']
                );
            }

            // ユーザー作成データ（シードのデフォルトを除く）が残っていないかチェック
            if (
                $currentGroup->shoppingItems()->exists()
                || $currentGroup->shoppingCategories()->where('is_default', false)->exists()
                || $currentGroup->shoppingTags()->exists()
                || $currentGroup->mealPlans()->exists()
                || $currentGroup->mealCategories()->where('is_default', false)->exists()
                || $currentGroup->recipes()->exists()
                || $currentGroup->recipeCategories()->exists()
                || $currentGroup->ingredients()->exists()
                || $currentGroup->ingredientUnits()->where('is_default', false)->exists()
                || $currentGroup->images()->exists()
            ) {
                throw new HttpException(
                    HttpStatusCode::CONFLICT->value,
                    __('api.invitation.has_existing_data'),
                    null,
                    ['X-Error-Type' => 'has_existing_data']
                );
            }
        }

        return true;
    }

    /**
     * Get the validated invitation token.
     *
     * @return InvitationToken|null
     */
    public function getInvitationToken(): ?InvitationToken
    {
        return $this->invitationToken;
    }

    /**
     * Get the operation key for error handling.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return __('operations.invitation.join');
    }
}
