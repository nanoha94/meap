<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        try {

            if ($request->user()->hasVerifiedEmail()) {
                return redirect()->intended(
                    config('app.frontend_url') . '/plan?verified=1'
                );
            }

            if ($request->user()->markEmailAsVerified()) {
                event(new Verified($request->user()));
            }

            return redirect()->intended(
                config('app.frontend_url') . '/plan?verified=1'
            );
        } catch (\Exception $e) {
            Log::error('Email verification error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id ?? 'unknown',
                'route_params' => $request->route()->parameters()
            ]);

            // エラーがあっても、フロントエンドにリダイレクト
            return redirect(
                config('app.frontend_url') . '/email/verify?error=' . urlencode($e->getMessage())
            );
        }
    }
}
