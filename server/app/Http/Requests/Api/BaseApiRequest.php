<?php

namespace App\Http\Requests\Api;

use App\Enums\HttpStatusCode;
use App\Http\Requests\BaseRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class BaseApiRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        try {
            $this->user()->groups()->sole();
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

        return true;
    }
}
