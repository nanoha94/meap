<?php

use App\Enums\HttpStatusCode;
use App\Traits\LoggingTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->dummy = new class {
        use LoggingTrait;

        public function testMethod($request)
        {
            $this->logInfo(HttpStatusCode::OK, 'テスト操作', 'テストメッセージ', $request, [], __METHOD__);
        }

        public function testErrorMethod($request)
        {
            $exception = new Exception('テストエラー');
            $this->logError(HttpStatusCode::INTERNAL_SERVER_ERROR, 'テスト操作', $exception, $request, [], __METHOD__);
        }

        public function testWarningMethod($request)
        {
            $this->logWarning(HttpStatusCode::OK, 'テスト操作', 'テスト警告', $request, [], __METHOD__);
        }

        public function testMethodWithSensitiveData($request)
        {
            $this->logInfo(HttpStatusCode::OK, 'テスト操作', '機密情報を含むテスト', $request, [
                'sensitive_data' => 'password123'
            ],  __METHOD__);
        }
    };
});

test('1-4-1: 基本動作テスト', function () {
    $request = Request::create('/test', 'GET');

    // ユーザー情報をシミュレート
    $user = new stdClass();
    $user->id = 1;
    $user->group = new stdClass();
    $user->group->id = 100;

    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「テスト操作」: テストメッセージ') &&
                str_contains($context['method'], 'testMethod') &&
                $context['user_id'] === 1 &&
                $context['group_id'] === 100 &&
                $context['request_method'] === 'GET' &&
                $context['status_code'] === HttpStatusCode::OK->value &&
                $context['request_data'] === [];
        });

    $this->dummy->testMethod($request);
});

test('1-4-2: リクエスト情報記録テスト', function () {
    $request = Request::create('/api/test?param=value', 'POST');

    $user = new stdClass();
    $user->id = 5;
    $user->group = new stdClass();
    $user->group->id = 400;

    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    // リクエスト情報が正しく記録されることを確認
    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「テスト操作」: テストメッセージ') &&
                str_contains($context['method'], 'testMethod') &&
                $context['user_id'] === 5 &&
                $context['group_id'] === 400 &&
                $context['status_code'] === HttpStatusCode::OK->value;
        });

    $this->dummy->testMethod($request);
});

test('1-4-3: 警告ログ出力テスト', function () {
    $request = Request::create('/test', 'PUT');

    $user = new stdClass();
    $user->id = 3;
    $user->group = new stdClass();
    $user->group->id = 200;

    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    // 警告ログが正しく呼ばれることを確認
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「テスト操作」: テスト警告') &&
                str_contains($context['method'], 'testWarningMethod') &&
                $context['user_id'] === 3 &&
                $context['group_id'] === 200 &&
                $context['status_code'] === HttpStatusCode::OK->value;
        });

    $this->dummy->testWarningMethod($request);
});

test('1-4-4: エラーログ出力テスト', function () {
    $request = Request::create('/test', 'POST');

    $user = new stdClass();
    $user->id = 2;
    $user->group = null;

    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    // エラーログが正しく呼ばれることを確認
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「テスト操作」: サーバー内部エラーが発生しました') &&
                str_contains($context['method'], 'testErrorMethod') &&
                str_contains($context['file'], 'LoggingTraitTest.php') &&
                is_int($context['line']) &&
                str_contains($context['trace'], 'testErrorMethod') &&
                $context['model'] === null &&  // getExceptionModel()はnullを返す
                $context['user_id'] === 2 &&
                $context['group_id'] === null &&
                $context['status_code'] === HttpStatusCode::INTERNAL_SERVER_ERROR->value;  // 整数値として比較
        });

    $this->dummy->testErrorMethod($request);
});

test('1-4-5: ログメッセージ統合テスト', function () {
    $request = Request::create('/test', 'GET');

    $user = new stdClass();
    $user->id = 6;
    $user->group = new stdClass();
    $user->group->id = 600;

    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    // Logファサードをモック
    Log::shouldReceive('info')->once();
    Log::shouldReceive('warning')->once();
    Log::shouldReceive('error')->once();

    $this->dummy->logInfo(HttpStatusCode::OK, 'テスト操作', 'テストメッセージ', $request, []);
    $this->dummy->logWarning(HttpStatusCode::OK, 'テスト操作', 'テストメッセージ', $request, []);
    $this->dummy->logError(HttpStatusCode::INTERNAL_SERVER_ERROR, 'テスト操作', new Exception('テストエラー'), $request, []);
});

test('1-4-6: 機密情報フィルタリングテスト', function () {
    $request = Request::create('/test', 'POST', [
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'current_password' => 'password123',
        'token' => 'sometoken',
        'api_token' => 'someapitoken',
        'api_key' => 'someapikey',
        'secret' => 'somesecret'
    ]);

    $user = new stdClass();
    $user->id = 7;
    $user->group = new stdClass();
    $user->group->id = 700;

    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    // 機密情報が正しくフィルタリングされることを確認
    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「テスト操作」: 機密情報を含むテスト') &&
                str_contains($context['method'], 'testMethodWithSensitiveData') &&
                $context['user_id'] === 7 &&
                $context['group_id'] === 700 &&
                $context['status_code'] === HttpStatusCode::OK->value &&
                $context['request_data']['password'] === '*****' &&
                $context['request_data']['password_confirmation'] === '*****' &&
                $context['request_data']['current_password'] === '*****' &&
                $context['request_data']['token'] === '*****' &&
                $context['request_data']['api_token'] === '*****' &&
                $context['request_data']['api_key'] === '*****' &&
                $context['request_data']['secret'] === '*****';
        });

    $this->dummy->testMethodWithSensitiveData($request);
});
