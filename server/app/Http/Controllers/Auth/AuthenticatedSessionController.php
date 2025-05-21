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
     *     description="ユーザーを認証してセッションを作成します。",
     *     tags={"Authentication"},
     *     security={},
     *     @OA\RequestBody(
     *         required=true,   
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", example="test@example.com"),
     *             @OA\Property(property="password", type="string", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ログイン成功"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="認証エラー"
     *     )
     * )
     */
    public function store(LoginRequest $request): Response
    {
        $request->authenticate();

        $request->session()->regenerate();

        return response()->noContent();
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
