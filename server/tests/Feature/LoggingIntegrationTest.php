<?php

use App\Enums\HttpStatusCode;
use App\Traits\LoggingTrait;
use App\Traits\ExceptionHandlerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

// 実際のログ出力をテストするためのクラス
class LoggingIntegrationTest
{
    use LoggingTrait, ExceptionHandlerTrait;

    public function testInfoLogging(Request $request)
    {
        $this->logInfo(HttpStatusCode::OK, 'ユーザー作成', '新しいユーザーが作成されました', $request, [
            'user_email' => 'test@example.com',
            'user_role' => 'admin'
        ]);

        return response()->json(['message' => 'success']);
    }

    public function testErrorLogging(Request $request)
    {
        try {
            throw new Exception('テストエラー');
        } catch (Exception $e) {
            $this->logError(HttpStatusCode::INTERNAL_SERVER_ERROR, 'ユーザー作成', $e, $request, [
                'additional_info' => 'テスト用の追加情報'
            ]);
        }
        return response()->json(['message' => 'error_logged'], HttpStatusCode::INTERNAL_SERVER_ERROR->value);
    }

    public function testWarningLogging(Request $request)
    {
        $this->logWarning(HttpStatusCode::OK, 'ユーザー作成', '重複するメールアドレスが検出されました', $request, [
            'duplicate_email' => 'test@example.com'
        ]);
        return response()->json(['message' => 'warning_logged'], HttpStatusCode::OK->value);
    }

    public function testValidationError(Request $request)
    {
        try {
            throw ValidationException::withMessages([
                'email' => ['メールアドレスが無効です'],
                'password' => ['パスワードは8文字以上必要です']
            ]);
        } catch (ValidationException $e) {
            return $this->handleException(
                $e,
                $request,
                '入力データが無効です',
                'ユーザー登録'
            );
        }
    }
}

test('実際のログ出力を確認（Info）', function () {
    // ログのモックを適切に設定
    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「ユーザー作成」: 新しいユーザーが作成されました') &&
                $context['user_email'] === 'test@example.com' &&
                $context['user_role'] === 'admin';
        });

    $controller = new LoggingIntegrationTest();
    $request = Request::create('/api/users', 'POST', [
        'email' => 'test@example.com',
        'password' => 'secret123'
    ]);

    $user = new stdClass();
    $user->id = 1;
    $user->group = new stdClass();
    $user->group->id = 100;

    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    // 実際のログ出力を確認
    $response = $controller->testInfoLogging($request);

    expect($response->getStatusCode())->toBe(200);
});

test('実際のログ出力を確認（Error）', function () {
    // ログのモックを適切に設定
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「ユーザー作成」: エラーが発生しました') &&
                $context['error_message'] === 'テストエラー' &&
                $context['status_code'] === HttpStatusCode::INTERNAL_SERVER_ERROR->value &&
                $context['additional_info'] === 'テスト用の追加情報';
        });

    $controller = new LoggingIntegrationTest();
    $request = Request::create('/api/users', 'POST', [
        'email' => 'test@example.com',
        'password' => 'secret123'
    ]);

    $request->setUserResolver(function () {
        return null;
    });

    // エラーログの出力を確認
    $response = $controller->testErrorLogging($request);

    expect($response->getStatusCode())->toBe(HttpStatusCode::INTERNAL_SERVER_ERROR->value);
});

test('実際のログ出力を確認（Warning）', function () {
    // ログのモックを適切に設定
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「ユーザー作成」: 重複するメールアドレスが検出されました') &&
                $context['duplicate_email'] === 'test@example.com' &&
                $context['status_code'] === HttpStatusCode::OK->value;
        });

    $controller = new LoggingIntegrationTest();
    $request = Request::create('/api/users', 'POST', [
        'email' => 'test@example.com'
    ]);

    $request->setUserResolver(function () {
        return null;
    });

    // 警告ログの出力を確認
    $response = $controller->testWarningLogging($request);

    expect($response->getStatusCode())->toBe(HttpStatusCode::OK->value);
});

test('実際のログ出力を確認（Validation Error）', function () {
    // ログのモックを適切に設定
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「ユーザー登録」: エラーが発生しました') &&
                isset($context['validation_errors']) &&
                $context['message'] === '入力データが無効です';
        });

    $controller = new LoggingIntegrationTest();
    $request = Request::create('/api/users', 'POST', [
        'email' => 'invalid-email',
        'password' => '123'
    ]);

    $request->setUserResolver(function () {
        return null;
    });

    // バリデーションエラーログの出力を確認
    $response = $controller->testValidationError($request);

    expect($response->getStatusCode())->toBe(HttpStatusCode::UNPROCESSABLE_ENTITY->value);
});

test('機密情報のフィルタリングを実際に確認', function () {
    // ログのモックを適切に設定
    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「ユーザー作成」: 新しいユーザーが作成されました') &&
                $context['request_data']['password'] === '*****' &&
                $context['request_data']['password_confirmation'] === '*****' &&
                $context['request_data']['api_token'] === '*****' &&
                $context['status_code'] === HttpStatusCode::OK->value;
        });

    $controller = new LoggingIntegrationTest();
    $request = Request::create('/api/users', 'POST', [
        'email' => 'test@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'api_token' => 'abc123xyz'
    ]);

    $request->setUserResolver(function () {
        return null;
    });

    // 機密情報を含むログの出力を確認
    $response = $controller->testInfoLogging($request);

    expect($response->getStatusCode())->toBe(HttpStatusCode::OK->value);
});
