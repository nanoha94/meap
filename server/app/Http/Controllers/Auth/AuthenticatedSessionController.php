<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Traits\ClearsSessionCookies;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    use ClearsSessionCookies;

    /**
     * @OA\Post(
     *     path="/login",
     *     summary="ログイン",
     *     tags={"Authentication"},
     *     security={},
     *     @OA\RequestBody(ref="#/components/requestBodies/UserLoginRequest"),
     *     @OA\Response(
     *         response=200, ref="#/components/responses/UserLoginSuccess"
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors"),
     * )
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $operation = __('operations.auth.login');
        $failedMessage = __('auth.failed', ['attribute' => __('auth.attributes.login')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                $request->authenticate();
                $request->session()->regenerate();
                $message = __('auth.success', ['attribute' => __('auth.attributes.login')]);
                return $this->successResponse(null, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     summary="ログアウト",
     *     tags={"Authentication"},
     *     security={"auth"},
     *     @OA\Response(
     *         response=200, ref="#/components/responses/UserLogoutSuccess"
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     * )
     */
    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        $response = $this->successResponse(null, __('auth.success', ['attribute' => __('auth.attributes.logout')]));

        return $this->clearSessionCookiesOnResponse($response);
    }
}
