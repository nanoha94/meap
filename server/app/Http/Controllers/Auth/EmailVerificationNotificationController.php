<?php

namespace App\Http\Controllers\Auth;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $operation = __('operations.auth.email_verification_notification');
        $failedMessage = __('auth.email_verification.store_failed');

        if ($request->user() === null) {
            $message = __('auth.email_verification.store_not_found');
            return $this->handleException(
                new Exception($message),
                $request,
                $message,
                $operation
            );
        }

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(config('app.frontend_url') . '/plan');
        }

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $request->user()->sendEmailVerificationNotification();
                $message = __('auth.email_verification.store_sent');
                return $this->successResponse(null, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
