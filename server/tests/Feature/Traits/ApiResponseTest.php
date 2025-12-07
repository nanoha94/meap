<?php

namespace Tests\Feature\Traits;

use Illuminate\Http\JsonResponse;
use App\Enums\HttpStatusCode;
use App\Traits\ApiResponse;

beforeEach(function () {
    // テスト用に Trait を使った匿名クラスを作成
    $this->dummy = new class {
        use ApiResponse;

        public function testSuccessResponse($data, $message)
        {
            return $this->successResponse($data, $message);
        }

        public function testIndexResponse($data, $total, $message)
        {
            return $this->indexResponse($data, $total, $message);
        }

        public function testSuccessResponseWithWarning($data, $message, $warning)
        {
            return $this->successResponseWithWarning($data, $message, $warning);
        }

        public function testCreatedResponse($data, $message)
        {
            return $this->createdResponse($data, $message);
        }

        public function testUpdatedResponse($data, $message)
        {
            return $this->updatedResponse($data, $message);
        }

        public function testDeletedResponse($message)
        {
            return $this->deletedResponse($message);
        }

        public function testShowResponse($data, $message)
        {
            return $this->showResponse($data, $message);
        }

        public function testErrorResponse($message, $statusCode = HttpStatusCode::BAD_REQUEST, $errors = [], ?string $errorType = '')
        {
            return $this->errorResponse($message, $statusCode, $errors, $errorType);
        }

        public function testNotFoundResponse($message)
        {
            return $this->notFoundResponse($message);
        }

        public function testUnauthorizedResponse($message)
        {
            return $this->unauthorizedResponse($message);
        }

        public function testForbiddenResponse($message)
        {
            return $this->forbiddenResponse($message);
        }

        public function testServerErrorResponse($message)
        {
            return $this->serverErrorResponse($message);
        }

        public function testDatabaseErrorResponse($message)
        {
            return $this->databaseErrorResponse($message);
        }
    };
});


test('1-1-1: 成功レスポンスをテスト', function () {
    $data = ['key' => 'value'];
    $message = 'Success message';
    $response = $this->dummy->testSuccessResponse($data, $message);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals([
        'success' => true,
        'message' => $message,
        'data' => $data,
    ], $response->getData(true));
});

test('1-1-2: 警告付き成功レスポンステスト', function () {
    $data = ['key' => 'value'];
    $message = 'Success message';
    $warning = 'Warning message';
    $response = $this->dummy->testSuccessResponseWithWarning($data, $message, $warning);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'warning' => $warning,
    ], $response->getData(true));
});

test('1-1-3: 警告なし成功レスポンステスト', function () {
    $data = ['key' => 'value'];
    $message = 'Success message';
    $response = $this->dummy->testSuccessResponse($data, $message);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals([
        'success' => true,
        'message' => $message,
        'data' => $data,
    ], $response->getData(true));
});

test('1-1-4: データ作成成功レスポンステスト', function () {
    $data = ['key' => 'value'];
    $message = 'Data created successfully';
    $response = $this->dummy->testCreatedResponse($data, $message);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(201, $response->getStatusCode());
    $this->assertEquals([
        'success' => true,
        'message' => $message,
        'data' => $data,
    ], $response->getData(true));
});

test('1-1-5: データ更新成功レスポンステスト', function () {
    $data = ['key' => 'value'];
    $message = 'Data updated successfully';
    $response = $this->dummy->testUpdatedResponse($data, $message);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals([
        'success' => true,
        'message' => $message,
        'data' => $data,
    ], $response->getData(true));
});

test('1-1-6: データ削除成功レスポンステスト', function () {
    $message = 'Data deleted successfully';
    $response = $this->dummy->testDeletedResponse($message);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals([
        'success' => true,
        'message' => $message,
        'data' => null,
    ], $response->getData(true));
});

test('1-1-7: データ一覧取得レスポンステスト', function () {
    $data = ['item1', 'item2'];
    $total = 2;
    $message = 'Data list retrieved successfully';
    $response = $this->dummy->testIndexResponse($data, $total, $message);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'total' => $total,
    ], $response->getData(true));
});

test('1-1-8: データ一覧取得レスポンステスト（total なし）', function () {
    $data = ['item1', 'item2'];
    $message = 'Data list retrieved successfully';
    $response = $this->dummy->testIndexResponse($data, null, $message);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals([
        'success' => true,
        'message' => $message,
        'data' => $data,
    ], $response->getData(true));
});

test('1-1-9: データ詳細取得レスポンステスト', function () {
    $data = ['key' => 'value'];
    $message = 'Data retrieved successfully';
    $response = $this->dummy->testShowResponse($data, $message);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals([
        'success' => true,
        'message' => $message,
        'data' => $data,
    ], $response->getData(true));
});

test('1-1-10: エラーレスポンステスト', function () {
    $message = 'Error occurred';
    $response = $this->dummy->testErrorResponse($message);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(400, $response->getStatusCode());
    $this->assertEquals([
        'success' => false,
        'message' => $message,
        'error_code' => 400,
        'error_type' => null,
        'error_description' => '不正なリクエスト',
        'errors' => [],
    ], $response->getData(true));
});

test('1-1-11: エラー詳細付きエラーレスポンステスト', function () {
    $message = 'Error occurred';
    $errors = ['field' => 'Invalid value'];
    $response = $this->dummy->testErrorResponse($message, HttpStatusCode::BAD_REQUEST, $errors);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(400, $response->getStatusCode());
    $this->assertEquals([
        'success' => false,
        'message' => $message,
        'error_code' => 400,
        'errors' => $errors,
        'error_type' => null,
        'error_description' => '不正なリクエスト',
    ], $response->getData(true));
});

test('1-1-12: エラータイプ付きエラーレスポンステスト', function () {
    $message = 'Error occurred';
    $errorType = 'validation_error';
    $response = $this->dummy->testErrorResponse($message, HttpStatusCode::BAD_REQUEST, [], $errorType);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(400, $response->getStatusCode());
    $this->assertEquals([
        'success' => false,
        'message' => $message,
        'error_code' => 400,
        'error_type' => $errorType,
        'error_description' => '不正なリクエスト',
        'errors' => [],
    ], $response->getData(true));
});

test('1-1-13: データ未発見エラーレスポンステスト', function () {
    $message = 'Data not found';
    $response = $this->dummy->testNotFoundResponse($message);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(404, $response->getStatusCode());
    $this->assertEquals([
        'success' => false,
        'message' => $message,
        'error_code' => 404,
        'error_type' => '',
        'error_description' => 'リソースが見つかりません',
        'errors' => [],
    ], $response->getData(true));
});

test('1-1-14: 認証エラーレスポンステスト', function () {
    $message = 'Unauthorized access';
    $response = $this->dummy->testUnauthorizedResponse($message);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(401, $response->getStatusCode());
    $this->assertEquals([
        'success' => false,
        'message' => $message,
        'error_code' => 401,
        'error_type' => '',
        'error_description' => '認証が必要です',
        'errors' => [],
    ], $response->getData(true));
});

test('1-1-15: 権限エラーレスポンステスト', function () {
    $message = 'Forbidden access';
    $response = $this->dummy->testForbiddenResponse($message);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(403, $response->getStatusCode());
    $this->assertEquals([
        'success' => false,
        'message' => $message,
        'error_code' => 403,
        'error_type' => '',
        'error_description' => 'アクセスが拒否されました',
        'errors' => [],
    ], $response->getData(true));
});

test('1-1-16: サーバーエラーレスポンステスト', function () {
    $message = 'Internal server error';
    $response = $this->dummy->testServerErrorResponse($message);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(500, $response->getStatusCode());
    $this->assertEquals([
        'success' => false,
        'message' => $message,
        'error_code' => 500,
        'error_type' => '',
        'error_description' => 'サーバー内部エラーが発生しました',
        'errors' => [],
    ], $response->getData(true));
});

test('1-1-17: データベースエラーレスポンステスト', function () {
    $message = 'Database error';
    $response = $this->dummy->testDatabaseErrorResponse($message);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(500, $response->getStatusCode());
    $this->assertEquals([
        'success' => false,
        'message' => $message,
        'error_code' => 500,
        'error_type' => '',
        'error_description' => 'サーバー内部エラーが発生しました',
        'errors' => [],
    ], $response->getData(true));
});
