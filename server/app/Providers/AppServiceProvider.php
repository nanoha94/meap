<?php

namespace App\Providers;

use App\Interfaces\AiRecipeParserInterface;
use App\Services\Ai\OpenAiRecipeParser;
use App\Enums\HttpStatusCode;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            AiRecipeParserInterface::class,
            match (config('services.ai.vision_provider')) {
                'openai' => OpenAiRecipeParser::class,
                default => throw new InvalidArgumentException(
                    'Unsupported AI vision provider: ' . config('services.ai.vision_provider'),
                ),
            },
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        URL::forceRootUrl(Config::get('app.url'));
        URL::forceScheme('https');

        // パスワードリセットURLの設定
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url') . "/password/reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        // パスワードのバリデーションルールを設定
        Password::defaults(function () {
            return Password::min(8)
                ->letters() // 英字を含む
                ->numbers() // 数字を含む
                ->symbols(); // 記号を含む
        });

        // AI API のレートリミットを設定（短期間の連続呼び出しを防止）
        // routes/api.php で throttle:ai ミドルウェアが適用されたルートで有効
        RateLimiter::for('ai', function (Request $request) {
            $limit = config('ai.rate_limit_per_minute', 3);

            return Limit::perMinute($limit)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => __('api.ai.usage.rate_limit_exceeded'),
                        'error_type' => 'ai_rate_limit_exceeded',
                        'error_code' => HttpStatusCode::TOO_MANY_REQUESTS->value,
                        'error_description' => HttpStatusCode::TOO_MANY_REQUESTS->getDescription(),
                        'errors' => [],
                    ], HttpStatusCode::TOO_MANY_REQUESTS->value, $headers);
                });
        });
    }
}
