<?php

namespace App\Http\Requests\Api;

use App\Enums\HttpStatusCode;
use App\Http\Requests\Api\BaseApiRequest;
use App\Models\InvitationToken;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvitationShowRequest extends BaseApiRequest
{
    protected $invitationToken;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // トークンの検証
        $token = $this->route('token');

        $this->invitationToken = InvitationToken::all()->first(function ($record) use ($token) {
            return Hash::check($token, $record->token);
        });

        // トークンが存在しない → 404 Not Found
        if (!$this->invitationToken) {
            throw new HttpException(
                HttpStatusCode::NOT_FOUND->value,
                __('api.invitation.token_not_found')
            );
        }

        // 有効期限切れ → 410 Gone
        if ($this->invitationToken->expires_at < now()) {
            throw new HttpException(
                HttpStatusCode::GONE->value,
                __('api.invitation.token_expired')
            );
        }

        return parent::authorize();
    }

    /**
     * Get the validated invitation token.
     *
     * @return InvitationToken|null
     */
    public function getInvitationToken()
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
        return __('operations.invitation.show');
    }
}
