<?php

namespace App\Http\Requests\Api;

use App\Enums\HttpStatusCode;
use App\Http\Requests\BaseRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class BaseApiRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $group = $user->group;

        // ユーザーがグループに所属していない場合は422エラー（前提条件が満たされていない）
        if (!$group || $group->id === null) {
            throw new HttpException(HttpStatusCode::UNPROCESSABLE_ENTITY->value, __('api.general.not_belong_to_any_group'));
        }
        return true;
    }
}
