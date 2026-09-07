<?php

namespace App\Providers;

use App\Enums\HttpStatusCode;
use App\Interfaces\AiRecipeParserInterface;
use App\Interfaces\RecipeOcrInterface;
use App\Models\Group;
use App\Services\Ai\GoogleVisionRecipeOcr;
use App\Services\Ai\OpenAiRecipeOcr;
use App\Services\Ai\OpenAiRecipeParser;
use Laravel\Cashier\Cashier;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Cashier::ignoreRoutes();

        $this->app->bind(
            RecipeOcrInterface::class,
            match (config('services.ai.ocr_provider')) {
                'openai' => OpenAiRecipeOcr::class,
                'google' => GoogleVisionRecipeOcr::class,
                default => throw new InvalidArgumentException(
                    'Unsupported AI OCR provider: ' . config('services.ai.ocr_provider'),
                ),
            },
        );

        $this->app->bind(AiRecipeParserInterface::class, OpenAiRecipeParser::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (!$this->app->runningInConsole() && blank(config('cashier.webhook.secret'))) {
            throw new RuntimeException(
                'STRIPE_WEBHOOK_SECRET must be set.',
            );
        }

        Cashier::useCustomerModel(Group::class);

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
