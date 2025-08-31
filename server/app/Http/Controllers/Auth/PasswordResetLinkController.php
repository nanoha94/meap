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
        $request->validate([
            'email' => ['required', 'email'],
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

            $statusCode = $statusMessages[$status] ?? 500; // それ以外は500として扱う

            if ($statusCode === 422) {
                throw ValidationException::withMessages([
                    'email' => [__($status)],
                ]);
            }

            try {
                $this->logError(HttpStatusCode::INTERNAL_SERVER_ERROR, __('operations.password.reset_link'), new Exception(__($status)), $request);
                return $this->errorResponse(__($status), HttpStatusCode::INTERNAL_SERVER_ERROR);
            } catch (Exception $e) {
                return  $this->handleException($e, $request, __('operations.password.reset_link'), 'password_reset_link.store');
            }
        }

        return $this->successResponse(null, __($status));
    }
}
