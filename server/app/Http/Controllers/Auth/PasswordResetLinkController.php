<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Custom\Auth\Interfaces\CustomPasswordBroker;
use App\Http\Controllers\Auth\AuthController;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use App\Traits\LoggingTrait;
use App\Enums\HttpStatusCode;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PasswordResetLinkController extends Controller
{
    use LoggingTrait;

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => ['required', 'email'],
            ], [
                'email.required' => __('validation.email.required'),
                'email.email' => __('validation.email.email'),
            ]);

            // We will send the password reset link to this user. Once we have attempted
            // to send the link, we will examine the response then see the message we
            // need to show to the user. Finally, we'll send out a proper response.
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status != Password::RESET_LINK_SENT) {
                $statusMessages = [
                    Password::INVALID_USER => 422,
                    Password::RESET_THROTTLED => 429,
                    CustomPasswordBroker::RETRY_TOKEN => 500
                ];

                if (!$statusMessages[$status]) {
                    throw new Exception(__($status));
                }

                $statusCode = $statusMessages[$status];

                if ($statusCode === 422) {
                    throw ValidationException::withMessages([
                        'email' => [__($status)],
                    ]);
                }


                throw new HttpException($statusCode, __($status));
            }

            return $this->successResponse(null, __($status));
        } catch (HttpException $e) {
            return $this->handleException(
                $e,
                $request,
                __($e->getMessage()),
                __('operations.password.reset_link')
            );
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.general.server_error'),
                __('operations.password.reset_link')
            );
        }
    }
}
