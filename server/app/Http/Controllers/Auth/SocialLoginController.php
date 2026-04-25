<?php

namespace App\Http\Controllers\Auth;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

/**
 * Google アカウントによる Web ログイン（Socialite リダイレクトフロー）。
 *
 * メール／パスワードの LoginRequest::authenticate() は使わず、Google で本人確認済みの User を
 * Auth::login() でセッションに載せる（OAuth コールバックにパスワードは含まれないため）。
 */
class SocialLoginController extends Controller
{
    private const PROVIDER_GOOGLE = 'google';

    /**
     * Google OAuth 同意画面へリダイレクトする。
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver(self::PROVIDER_GOOGLE)->redirect();
    }

    /**
     * Google OAuth コールバック。ソーシャル紐付け・ユーザー作成後、セッションにログインしてフロントの /plan へ送る。
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        // code と state を検証し、アクセストークン経由で Google 上のプロフィールを取得
        try {
            $googleUser = Socialite::driver(self::PROVIDER_GOOGLE)->user();
        } catch (InvalidStateException $e) {
            // セッション欠如・CSRF state 不一致など（別タブ・セッション期限切れが典型）
            $this->logError(
                HttpStatusCode::BAD_REQUEST,
                __('operations.auth.login'),
                $e,
                $request,
                ['oauth' => 'invalid_state'],
            );

            return redirect(config('app.frontend_url') . '/login?error=oauth_state_invalid');
        } catch (Throwable $e) {
            // トークン取得失敗・Google API エラーなど
            $this->logError(
                HttpStatusCode::INTERNAL_SERVER_ERROR,
                __('operations.auth.login'),
                $e,
                $request,
                ['oauth' => 'callback_failed'],
            );

            return redirect(config('app.frontend_url') . '/login?error=oauth_failed');
        }

        $email = $googleUser->getEmail();
        if ($email === null || $email === '') {
            // スコープ不足などでメールが返らない場合のフォールバック
            return redirect(config('app.frontend_url') . '/login?error=oauth_no_email');
        }

        $providerId = $googleUser->getId();

        // 既に Google ID で紐付いていれば、そのユーザーでログイン
        $social = SocialAccount::query()
            ->where('provider', self::PROVIDER_GOOGLE)
            ->where('provider_id', $providerId)
            ->first();

        if ($social !== null) {
            return $this->loginAndRedirect($request, $social->user);
        }

        // 同一メールの既存ユーザーがいれば social_accounts を追加して連携（パスワード不要ログイン）
        $user = User::query()->where('email', $email)->first();

        if ($user !== null) {
            DB::transaction(function () use ($user, $providerId): void {
                SocialAccount::create([
                    'user_id' => $user->id,
                    'provider' => self::PROVIDER_GOOGLE,
                    'provider_id' => $providerId,
                ]);
                // Google 連携時はメール確認済みとみなす（未確認の既存ユーザーもここで確定させる）
                if ($user->email_verified_at === null) {
                    $user->email_verified_at = now();
                    $user->save();
                }
            });

            return $this->loginAndRedirect($request, $user->fresh());
        }

        // 新規ユーザー: RegisteredUserController と同様にグループ作成まで行う（password は nullable）
        $name = $googleUser->getName();
        if ($name === null || $name === '') {
            $name = explode('@', $email)[0] ?: 'User';
        }

        $user = DB::transaction(function () use ($name, $email, $providerId): User {
            $newUser = User::create([
                'name' => $name,
                'email' => $email,
                'avatar_seed' => User::generateUniqueCustomId(),
            ]);
            $newUser->email_verified_at = now();
            $newUser->save();

            $group = Group::createGroup();
            $group->users()->attach($newUser->id);

            SocialAccount::create([
                'user_id' => $newUser->id,
                'provider' => self::PROVIDER_GOOGLE,
                'provider_id' => $providerId,
            ]);

            return $newUser;
        });

        return $this->loginAndRedirect($request, $user);
    }

    /**
     * web ガードのセッションにユーザーを載せ、セッション ID を再生成してからフロントの /plan へ。
     */
    private function loginAndRedirect(Request $request, User $user): RedirectResponse
    {
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect(config('app.frontend_url') . '/plan');
    }
}
