<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordResetLinkRequest;
use App\Custom\Auth\Interfaces\CustomPasswordBroker;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use App\Traits\LoggingTrait;
use App\Enums\HttpStatusCode;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PasswordResetLinkController extends Controller
{
    use LoggingTrait;

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(PasswordResetLinkRequest $request): JsonResponse
    {
        try {
            // We will send the password reset link to this user. Once we have attempted
            // to send the link, we will examine the response then see the message we
            // need to show to the user. Finally, we'll send out a proper response.
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status != Password::RESET_LINK_SENT) {
                $statusMessages = [
                    Password::INVALID_USER => HttpStatusCode::UNPROCESSABLE_ENTITY, // 422
                    Password::RESET_THROTTLED => HttpStatusCode::TOO_MANY_REQUESTS, // 429
                    CustomPasswordBroker::RETRY_TOKEN => HttpStatusCode::INTERNAL_SERVER_ERROR // 500
                ];

                if (!$statusMessages[$status]) {
                    throw new Exception(__($status));
                }

                $statusCode = $statusMessages[$status]  ?? HttpStatusCode::INTERNAL_SERVER_ERROR;

                return $this->handleException(
                    new HttpException($statusCode->value, __($status)),
                    $request,
                    __($status),
                    __('operations.auth.password_reset_link')
                );


                throw new HttpException($statusCode, __($status));
            }

            return $this->successResponse(null, __($status));
        } catch (HttpException $e) {
            return $this->handleException(
                $e,
                $request,
                __($e->getMessage()),
                __('operations.auth.password_reset_link')
            );
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.general.server_error'),
                __('operations.auth.password_reset_link')
            );
        }
    }
}
