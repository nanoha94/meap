<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupUserMapping;
use App\Models\User;
use Exception;
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
            $this->logError(__('operations.auth.registration'), new Exception(__('api.auth.already_logged_in')), $request);
            return $this->errorResponse(__('api.auth.already_logged_in'), 409);
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
                return $this->successResponse(null, __('api.auth.registration_success'));
            }
            $this->logError(__('operations.auth.registration'), new Exception(__('api.auth.registration_failed')), $request);
            return $this->errorResponse(__('api.auth.registration_failed'), 500);
        } catch (\Throwable $e) {
            $this->logError(__('operations.auth.registration'), $e, $request);
            return $this->errorResponse(__('api.auth.registration_failed'), 500, $e->getMessage());
        }
    }
}
