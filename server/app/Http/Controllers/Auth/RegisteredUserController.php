<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\Group;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
    public function store(RegisterUserRequest $request): Response|JsonResponse
    {
        $operation = __('operations.auth.register_user');
        $failedMessage = __('auth.failed', ['attribute' => __('auth.attributes.register')]);

        return $this->executeWithExceptionHandling(
            function () use ($request) {
                // トランザクション開始
                DB::beginTransaction();

                try {
                    // ユーザー作成時に、グループも作成して紐づけする
                    $user = User::create([
                        'name' => $request->name,
                        'email' => $request->email,
                        'password' => Hash::make($request->string('password')),
                        'avatar_seed' => User::generateUniqueCustomId(),
                    ]);

                    $group = Group::createGroup();

                    $group->users()->attach($user->id);

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e; // 外側のexecuteWithExceptionHandlingで処理される
                }

                event(new Registered($user));
                Auth::login($user);
                $request->session()->regenerate();
                $message = __('auth.success', ['attribute' => __('auth.attributes.register')]);
                return $this->successResponse(null, $message);
            },
            $request,
            $failedMessage,
            $operation
        );
    }
}
