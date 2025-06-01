<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
     *         response=204, ref="#/components/responses/UserLoginSuccess"
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors"),
     * )
     */
    public function store(LoginRequest $request): Response
    {
        $request->authenticate();

        $request->session()->regenerate();

        return response()->noContent();
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     summary="ログアウト",
     *     tags={"Authentication"},
     *     security={"auth"},
     *     @OA\Response(
     *         response=204, ref="#/components/responses/UserLogoutSuccess"
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     * )
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
