<?php

use App\Enums\HttpStatusCode;
use App\Traits\LoggingTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// テスト用のダミークラス
class LoggingTraitTest
{
    use LoggingTrait;

    public function testMethod(Request $request)
    {
        $this->logInfo(HttpStatusCode::OK, 'テスト操作', 'テストメッセージ', $request, [], __FUNCTION__);
    }

    public function testErrorMethod(Request $request)
    {
        $exception = new Exception('テストエラー');
        $this->logError(HttpStatusCode::INTERNAL_SERVER_ERROR, 'テスト操作', $exception, $request, [], __FUNCTION__);
    }

    public function testWarningMethod(Request $request)
    {
        $this->logWarning(HttpStatusCode::OK, 'テスト操作', 'テスト警告', $request, [], __FUNCTION__);
    }

    public function testMethodWithSensitiveData(Request $request)
    {
        $this->logInfo(HttpStatusCode::OK, 'テスト操作', '機密情報を含むテスト', $request, [
            'sensitive_data' => 'password123'
        ], __FUNCTION__);
    }
}

test('LoggingTraitの基本動作をテスト', function () {
    $controller = new LoggingTraitTest();
    $request = Request::create('/test', 'GET');

    // ユーザー情報をシミュレート
    $user = new stdClass();
    $user->id = 1;
    $user->group = new stdClass();
    $user->group->id = 100;

    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    // ログメッセージが正しく呼ばれることを確認
    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「テスト操作」: テストメッセージ') &&
                $context['controller'] === 'LoggingTraitTest' &&
                $context['method'] === 'testMethod' &&
                $context['user_id'] === 1 &&
                $context['group_id'] === 100 &&
                $context['status_code'] === HttpStatusCode::OK->value;
        });

    $controller->testMethod($request);
});

test('エラーログの出力をテスト', function () {
    $controller = new LoggingTraitTest();
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
            return str_contains($message, '操作「テスト操作」: エラーが発生しました') &&
                $context['error_message'] === 'テストエラー' &&
                $context['method'] === 'testErrorMethod' &&
                str_contains($context['file'], 'LoggingTraitTest.php') &&
                is_int($context['line']) &&
                str_contains($context['trace'], 'LoggingTraitTest->testErrorMethod') &&
                $context['model'] === null &&  // getExceptionModel()はnullを返す
                $context['user_id'] === 2 &&
                $context['group_id'] === null &&
                $context['status_code'] === HttpStatusCode::INTERNAL_SERVER_ERROR->value;  // 整数値として比較
        });

    $controller->testErrorMethod($request);
});

test('警告ログの出力をテスト', function () {
    $controller = new LoggingTraitTest();
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
                $context['controller'] === 'LoggingTraitTest' &&
                $context['method'] === 'testWarningMethod' &&
                $context['user_id'] === 3 &&
                $context['group_id'] === 200 &&
                $context['status_code'] === HttpStatusCode::OK->value;
        });

    $controller->testWarningMethod($request);
});

test('機密情報のフィルタリングをテスト', function () {
    $controller = new LoggingTraitTest();
    $request = Request::create('/test', 'POST', ['password' => 'secret123']);

    $user = new stdClass();
    $user->id = 4;
    $user->group = new stdClass();
    $user->group->id = 300;

    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    // 機密情報がフィルタリングされることを確認
    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「テスト操作」: 機密情報を含むテスト') &&
                $context['sensitive_data'] === 'password123' &&
                $context['request_data']['password'] === '*****' &&
                $context['user_id'] === 4 &&
                $context['group_id'] === 300 &&
                $context['status_code'] === HttpStatusCode::OK->value;
        });

    $controller->testMethodWithSensitiveData($request);
});

test('リクエスト情報が正しく記録されることをテスト', function () {
    $controller = new LoggingTraitTest();
    $request = Request::create('/test', 'DELETE', ['param' => 'value']);

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
                $context['controller'] === 'LoggingTraitTest' &&
                $context['method'] === 'testMethod' &&  // 修正: 呼び出し元のメソッド名を期待
                $context['user_id'] === 5 &&
                $context['group_id'] === 400 &&
                $context['status_code'] === HttpStatusCode::OK->value;
        });

    $controller->testMethod($request);
});
