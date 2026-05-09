<?php

use App\Traits\ExceptionHandlerTrait;
use App\Enums\HttpStatusCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;


beforeEach(function () {
    $this->dummy = new class {
        use ExceptionHandlerTrait;

        public function testHandleValidationException($request)
        {
            $exception = ValidationException::withMessages([
                'email' => ['メールアドレスが無効です'],
                'password' => ['パスワードは必ず指定してください。',]
            ]);

            return $this->handleException(
                $exception,
                $request,
                'バリデーションエラーが発生しました',
                'ユーザー登録'
            );
        }

        public function testHandleHttpException($request)
        {
            $exception = new HttpException(403, 'HTTPエラーが発生しました');

            return $this->handleException(
                $exception,
                $request,
                'HTTPエラーが発生しました',
                'テスト操作'
            );
        }

        public function testHandleModelNotFoundException($request)
        {
            $exception = new ModelNotFoundException();
            $exception->setModel('User', [1]);

            return $this->handleException(
                $exception,
                $request,
                'ユーザーが見つかりませんでした',
                'ユーザー検索'
            );
        }

        public function testHandleQueryException($request)
        {
            $exception = new QueryException('mysql', 'SELECT * FROM users', [], new Exception('SQLSTATE[42S02]: Base table or view not found'));

            return $this->handleException(
                $exception,
                $request,
                'データベースエラーが発生しました',
                'ユーザー取得'
            );
        }

        public function testHandleGenericException($request)
        {
            $exception = new Exception('予期しないエラーが発生しました');

            return $this->handleException(
                $exception,
                $request,
                'システムエラーが発生しました',
                'データ処理'
            );
        }

        public function testHandleCustomException($request)
        {
            $exception = new class extends Exception {
                public function getStatusCode()
                {
                    return 418;
                }
            };

            return $this->handleException(
                $exception,
                $request,
                'カスタムエラーが発生しました',
                'テスト操作'
            );
        }
    };
});

test('1-3-1: 【handleException】 ValidationException 処理テスト', function () {
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
            return str_contains($message, '操作「ユーザー登録」: 入力内容に誤りがあります') &&
                isset($context['errors']) &&
                $context['message'] === 'バリデーションエラーが発生しました' &&
                $context['status_code'] === HttpStatusCode::UNPROCESSABLE_ENTITY->value;
        });

    $response = $this->dummy->testHandleValidationException($request);

    // レスポンスの内容を確認
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getStatusCode())->toBe(HttpStatusCode::UNPROCESSABLE_ENTITY->value);

    $responseData = json_decode($response->getContent(), true);
    expect($responseData['message'])->toBe('バリデーションエラーが発生しました');
});

test('1-3-2: HttpExceptionの処理をテスト', function () {
    $request = Request::create('/api/test', 'GET');

    $request->setUserResolver(function () {
        return null;
    });

    // ログが正しく呼ばれることを確認
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「テスト操作」: アクセスが拒否されました') &&
                $context['message'] === 'HTTPエラーが発生しました' &&
                $context['status_code'] === 403;
        });

    $response = $this->dummy->testHandleHttpException($request);

    // レスポンスの内容を確認
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getStatusCode())->toBe(403);

    $responseData = json_decode($response->getContent(), true);
    expect($responseData['message'])->toBe('HTTPエラーが発生しました');
});

test('1-3-3: 【handleException】 ModelNotFoundException 処理テスト', function () {
    $request = Request::create('/api/users/1', 'GET');

    $request->setUserResolver(function () {
        return null;
    });

    // ログが正しく呼ばれることを確認
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「ユーザー検索」: リソースが見つかりません') &&
                isset($context['search_conditions']) &&
                $context['message'] === 'ユーザーが見つかりませんでした' &&
                $context['status_code'] === HttpStatusCode::NOT_FOUND->value;
        });

    $response = $this->dummy->testHandleModelNotFoundException($request);

    // レスポンスの内容を確認
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getStatusCode())->toBe(HttpStatusCode::NOT_FOUND->value);

    $responseData = json_decode($response->getContent(), true);
    expect($responseData['message'])->toBe('ユーザーが見つかりませんでした');
});

test('1-3-4: 【handleException】 QueryException 処理テスト', function () {
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
            return str_contains($message, '操作「ユーザー取得」: サーバー内部エラーが発生しました') &&
                $context['message'] === 'データベースエラーが発生しました' &&
                $context['status_code'] === HttpStatusCode::INTERNAL_SERVER_ERROR->value;
        });

    $response = $this->dummy->testHandleQueryException($request);

    // レスポンスの内容を確認
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getStatusCode())->toBe(HttpStatusCode::INTERNAL_SERVER_ERROR->value);

    $responseData = json_decode($response->getContent(), true);
    expect($responseData['message'])->toBe('データベースエラーが発生しました');
});

test('1-3-5: 【handleGenericException】 汎用例外処理テスト', function () {
    $request = Request::create('/api/process', 'POST');

    $request->setUserResolver(function () {
        return null;
    });

    // ログが正しく呼ばれることを確認
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「データ処理」: サーバー内部エラーが発生しました') &&
                $context['message'] === 'システムエラーが発生しました' &&
                $context['status_code'] === HttpStatusCode::INTERNAL_SERVER_ERROR->value;
        });

    $response = $this->dummy->testHandleGenericException($request);

    // レスポンスの内容を確認
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getStatusCode())->toBe(HttpStatusCode::INTERNAL_SERVER_ERROR->value);

    $responseData = json_decode($response->getContent(), true);
    expect($responseData['message'])->toBe('システムエラーが発生しました');
});

test('1-3-6: 【getExceptionStatusCode】 カスタムステータスコードテスト', function () {
    $request = Request::create('/api/test', 'GET');

    $request->setUserResolver(function () {
        return null;
    });

    // ログが正しく呼ばれることを確認
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, '操作「テスト操作」: エラーが発生しました。') &&
                $context['message'] === 'カスタムエラーが発生しました' &&
                $context['status_code'] === 418;
        });

    $response = $this->dummy->testHandleCustomException($request);

    // レスポンスの内容を確認
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getStatusCode())->toBe(418);

    $responseData = json_decode($response->getContent(), true);
    expect($responseData['message'])->toBe('カスタムエラーが発生しました');
});
