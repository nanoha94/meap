<?php

use App\Traits\ExceptionHandlerTrait;
use App\Enums\HttpStatusCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

// テスト用のダミークラス
class ExceptionHandlerTraitTest
{
    use ExceptionHandlerTrait;

    public function testHandleValidationException(Request $request)
    {
        $exception = ValidationException::withMessages([
            'email' => ['メールアドレスが無効です'],
            'password' => ['パスワードは必須です']
        ]);

        return $this->handleException(
            $exception,
            $request,
            'バリデーションエラーが発生しました',
            'ユーザー登録'
        );
    }

    public function testHandleModelNotFoundException(Request $request)
    {
        $exception = new ModelNotFoundException();
        $exception->setModel('User', [1]);

        return $this->handleException(
            $exception,
            $request,
            'ユーザーが見つかりません',
            'ユーザー検索'
        );
    }

    public function testHandleQueryException(Request $request)
    {
        // QueryExceptionの正しい引数: connectionName, sql, bindings, previous
        $exception = new QueryException('mysql', 'SELECT * FROM users', [], new Exception('SQLSTATE[42S02]: Base table or view not found'));

        return $this->handleException(
            $exception,
            $request,
            'データベースエラーが発生しました',
            'ユーザー取得'
        );
    }

    public function testHandleGenericException(Request $request)
    {
        $exception = new Exception('予期しないエラーが発生しました');

        return $this->handleException(
            $exception,
            $request,
            'システムエラーが発生しました',
            'データ処理'
        );
    }
}

test('ValidationExceptionの処理をテスト', function () {
    $handler = new ExceptionHandlerTraitTest();
    $request = Request::create('/api/users', 'POST');

    $request->setUserResolver(function () {
        $user = new stdClass();
        $user->id = 1;
        $user->group = null;
        return $user;
    });

    // ログが正しく呼ばれることを確認
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「ユーザー登録」: エラーが発生しました') &&
                isset($context['validation_errors']) &&
                $context['message'] === 'バリデーションエラーが発生しました' &&
                $context['status_code'] === HttpStatusCode::UNPROCESSABLE_ENTITY->value;
        });

    $response = $handler->testHandleValidationException($request);

    // レスポンスの内容を確認
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getStatusCode())->toBe(HttpStatusCode::UNPROCESSABLE_ENTITY->value);

    $responseData = json_decode($response->getContent(), true);
    expect($responseData['message'])->toBe('バリデーションエラーが発生しました');
});

test('ModelNotFoundExceptionの処理をテスト', function () {
    $handler = new ExceptionHandlerTraitTest();
    $request = Request::create('/api/users/1', 'GET');

    $request->setUserResolver(function () {
        return null;
    });

    // ログが正しく呼ばれることを確認
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「ユーザー検索」: エラーが発生しました') &&
                isset($context['search_conditions']) &&
                $context['message'] === 'ユーザーが見つかりません' &&
                $context['status_code'] === HttpStatusCode::NOT_FOUND->value;
        });

    $response = $handler->testHandleModelNotFoundException($request);

    // レスポンスの内容を確認
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getStatusCode())->toBe(HttpStatusCode::NOT_FOUND->value);

    $responseData = json_decode($response->getContent(), true);
    expect($responseData['message'])->toBe('ユーザーが見つかりません');
});

test('QueryExceptionの処理をテスト', function () {
    $handler = new ExceptionHandlerTraitTest();
    $request = Request::create('/api/users', 'GET');

    $request->setUserResolver(function () {
        $user = new stdClass();
        $user->id = 2;
        $user->group = new stdClass();
        $user->group->id = 100;
        return $user;
    });

    // ログが正しく呼ばれることを確認
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「ユーザー取得」: エラーが発生しました') &&
                $context['message'] === 'データベースエラーが発生しました' &&
                $context['status_code'] === HttpStatusCode::INTERNAL_SERVER_ERROR->value;
        });

    $response = $handler->testHandleQueryException($request);

    // レスポンスの内容を確認
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getStatusCode())->toBe(HttpStatusCode::INTERNAL_SERVER_ERROR->value);

    $responseData = json_decode($response->getContent(), true);
    expect($responseData['message'])->toBe('データベースエラーが発生しました');
});

test('汎用例外の処理をテスト', function () {
    $handler = new ExceptionHandlerTraitTest();
    $request = Request::create('/api/process', 'POST');

    $request->setUserResolver(function () {
        return null;
    });

    // ログが正しく呼ばれることを確認
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「データ処理」: エラーが発生しました') &&
                $context['message'] === 'システムエラーが発生しました' &&
                $context['status_code'] === HttpStatusCode::INTERNAL_SERVER_ERROR->value;
        });

    $response = $handler->testHandleGenericException($request);

    // レスポンスの内容を確認
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getStatusCode())->toBe(HttpStatusCode::INTERNAL_SERVER_ERROR->value);

    $responseData = json_decode($response->getContent(), true);
    expect($responseData['message'])->toBe('システムエラーが発生しました');
});
