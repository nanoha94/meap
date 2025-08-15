<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupUserMapping;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * @OA\Post(
     *     path="/register",
     *     summary="アカウント登録",
     *     tags={"Authentication"},
     *     security={},
     *     @OA\RequestBody(ref="#/components/requestBodies/UserRegisterRequest"),
     *     @OA\Response(
     *         response=204, ref="#/components/responses/UserRegisterSuccess"
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=409, ref="#/components/responses/UserAlreadyLoggedIn"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationErrors"),
     *     @OA\Response(response=500, ref="#/components/responses/UnexpectedError"),
     * )
     *
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): Response|JsonResponse
    {
        // ログイン状態をチェック
        if (Auth::check()) {
            Log::info('ログイン中のユーザーが登録を試行', [
                'user_id' => Auth::id(),
                'email' => Auth::user()->email,
                'requested_email' => $request->email
            ]);

            return $this->errorResponse('既にログインしています。新しいアカウントを作成するには、まずログアウトしてください。', 409);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            $user = DB::transaction(function () use ($request) {
                // ユーザー作成時に、グループも作成して紐づけする
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->string('password')),
                    'avatar_seed' => User::generateUniqueCustomId(),
                ]);

                $group = Group::createGroup();

                GroupUserMapping::create(['user_id' => $user->id, 'group_id' => $group->id]);

                return $user;
            });

            if ($user) {
                event(new Registered($user));
                Auth::login($user);
                return $this->successResponse(null, 'ユーザー登録に成功しました。');
            }
            return $this->errorResponse('ユーザー登録に失敗しました。', 500);
        } catch (\Throwable $e) {
            Log::error('ユーザー登録エラー', [
                'function' => 'RegisteredUserController@store',
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('ユーザー登録に失敗しました。', 500, $e->getMessage());
        }
    }
}
