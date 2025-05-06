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
            Log::info('Email verification process started', [
                'user_id' => $request->user()->id ?? 'unknown',
                'email' => $request->user()->email ?? 'unknown',
                'hash' => $request->route('hash') ?? 'unknown'
            ]);

            if ($request->user()->hasVerifiedEmail()) {
                Log::info('User email already verified', ['user_id' => $request->user()->id]);
                return redirect()->intended(
                    config('app.frontend_url') . '/plan?verified=1'
                );
            }

            if ($request->user()->markEmailAsVerified()) {
                Log::info('Email marked as verified', ['user_id' => $request->user()->id]);
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
