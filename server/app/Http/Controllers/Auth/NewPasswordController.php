<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class NewPasswordController extends Controller
{
    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'password' => ['required', 'confirmed', 'min:8', Rules\Password::defaults()],
            'password_confirmation' => ['required', 'min:8'],
        ]);

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
                Password::INVALID_USER => 422,
                Password::INVALID_TOKEN => 404,
            ];

            $statusCode = $statusMessages[$status] ?? 500; // それ以外は500として扱う

            return response()->json([
                'message' => __($status),
            ], $statusCode);
        }

        App::setLocale('en');
        return response()->json(['status' => __($status)]);
    }
}
