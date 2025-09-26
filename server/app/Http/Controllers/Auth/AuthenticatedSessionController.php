<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
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
        $request->authenticate();

        $request->session()->regenerate();

        return $this->successResponse(null, __('auth.login.success'));
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

        $response = $this->successResponse(null, __('auth.logout.success'));

        // 複数のドメインとパスパターンで削除
        $domains = [config('session.domain'), null, '', '.' . parse_url(config('app.url'), PHP_URL_HOST)];
        $paths = [config('session.path'), '/', ''];

        foreach ($domains as $d) {
            foreach ($paths as $p) {
                $response->cookie(
                    config('session.cookie'),
                    '',
                    -1,
                    $p,
                    $d,
                    config('session.secure'),
                    config('session.http_only'),
                    false,
                    config('session.same_site')
                );

                $response->cookie(
                    'XSRF-TOKEN',
                    '',
                    -1,
                    $p,
                    $d,
                    config('session.secure'),
                    false,
                    false,
                    config('session.same_site')
                );
            }
        }


        return $response;
    }
}
