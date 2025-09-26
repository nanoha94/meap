<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\NewPasswordRequest;
use App\Traits\LoggingTrait;
use App\Enums\HttpStatusCode;
use Exception;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NewPasswordController extends Controller
{
    use LoggingTrait;

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(NewPasswordRequest $request): JsonResponse
    {
        try {
            // Here we will attempt to reset the user's password. If it is successful we
            // will update the password on an actual user model and persist it to the
            // database. Otherwise we will parse the error and return the response.
            $status = Password::reset(
                ['password' => $request->string('password'), 'password_confirmation' => $request->string('password_confirmation'), 'token' => $request->input('token')],
                function ($user) use ($request) {
                    $user->forceFill([
                        'password' => Hash::make($request->string('password')),
                        'remember_token' => Str::random(60),
                    ])->save();

                    event(new PasswordReset($user));
                }
            );

            if ($status != Password::PASSWORD_RESET) {
                $statusMessages = [
                    Password::INVALID_USER => HttpStatusCode::UNPROCESSABLE_ENTITY,    // 422
                    Password::INVALID_TOKEN => HttpStatusCode::NOT_FOUND,             // 404
                ];

                // 適切なステータスコードを取得
                $statusCode = $statusMessages[$status] ?? HttpStatusCode::INTERNAL_SERVER_ERROR;

                return $this->handleException(
                    new HttpException($statusCode->value, __($status)),
                    $request,
                    __($status),
                    __('operations.auth.password_reset')
                );
            }

            App::setLocale('en');
            return $this->successResponse(null, __($status));
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                $request,
                __('api.general.server_error'),
                __('operations.auth.password_reset')
            );
        }
    }
}
