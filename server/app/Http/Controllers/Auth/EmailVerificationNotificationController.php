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
        if ($request->user() === null) {
            $message = __('auth.email_verification_notification.store_not_found');
            $this->logError(HttpStatusCode::NOT_FOUND, __('operations.auth.email_verification_notification'), new Exception($message), $request, [
                'email' => $request->user()->email
            ]);
            return $this->errorResponse($message, HttpStatusCode::NOT_FOUND);
        }

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(config('app.frontend_url') . '/plan');
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('auth.email_verification_notification.store_failed'),
                __('operations.auth.email_verification_notification')
            );
        }

        return $this->successResponse(null, __('auth.email_verification_notification.store_sent'));
    }
}
