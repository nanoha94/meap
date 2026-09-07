<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('2-6-1: 正常なメール確認', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(config('app.frontend_url') . '/plan?verified=1');
});

test('2-6-2: メール確認の冪等性確認', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    // 既に確認済みの場合はイベントが発火されない
    Event::assertNotDispatched(Verified::class);
    $response->assertRedirect(config('app.frontend_url') . '/plan');
});

test('2-6-3: リダイレクトパラメータ確認（verified=1）', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    $response->assertRedirect(config('app.frontend_url') . '/plan?verified=1');
});

test('2-6-4: Verified イベント発火確認', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class, function ($event) use ($user) {
        return $event->user->id === $user->id;
    });
});

test('2-6-5: 間違ったハッシュ値', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    // エラータイプベースのリダイレクト（セキュリティ改善）
    $response->assertRedirect();
    $response->assertRedirectContains('/email/verify?error=invalid_link');
});

test('2-6-6: 未認証ユーザーのアクセス', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->get($verificationUrl);

    // エラータイプベースのリダイレクト（セキュリティ改善）
    $response->assertRedirect();
    $response->assertRedirectContains('/email/verify?error=unauthenticated');
});

test('2-6-7: 無効なパラメータ形式', function () {
    $user = User::factory()->unverified()->create();

    // 存在しないユーザーIDでアクセス（署名は正しく生成）
    $nonExistentUserId = 99999;
    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $nonExistentUserId, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    // エラータイプベースのリダイレクト（セキュリティ改善）
    $response->assertRedirect();
    $response->assertRedirectContains('/email/verify?error=invalid_link');
});

test('2-6-8: 無効な署名', function () {
    $user = User::factory()->unverified()->create();

    // 署名を改ざん
    $url = route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]);
    $tamperedUrl = $url . '&signature=tampered';

    $response = $this->actingAs($user)->get($tamperedUrl);

    // 署名検証はミドルウェアレベルで行われ、403 Forbiddenが返される
    $response->assertStatus(403);
});

test('2-6-9: 署名なしの URL', function () {
    $user = User::factory()->unverified()->create();

    // 署名なしのURL
    $url = route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]);

    $response = $this->actingAs($user)->get($url);

    // 署名検証はミドルウェアレベルで行われ、403 Forbiddenが返される
    $response->assertStatus(403);
});

test('2-6-10: 期限切れの署名', function () {
    $user = User::factory()->unverified()->create();

    // 期限切れの署名付きURL（過去の時間を指定）
    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->subMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    // 署名検証はミドルウェアレベルで行われ、403 Forbiddenが返される
    $response->assertStatus(403);
});

test('2-6-11: レート制限（1分間に6回超過）', function () {
    $user = User::factory()->unverified()->create();

    // レート制限をクリア
    Cache::forget('throttle:verification.verify:' . $user->id);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    // 6回リクエストを送信
    for ($i = 0; $i < 6; $i++) {
        $this->actingAs($user)->get($verificationUrl);
    }

    // 7回目のリクエストでレート制限に引っかかる
    $response = $this->actingAs($user)->get($verificationUrl);

    $response->assertStatus(429);
});

test('2-6-12: レート制限リセット', function () {
    $user = User::factory()->unverified()->create();

    // レート制限をクリア
    Cache::forget('throttle:verification.verify:' . $user->id);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    // 1分経過をシミュレーション（Cacheをクリアすることで代替）
    Cache::flush();

    $response = $this->actingAs($user)->get($verificationUrl);

    $response->assertStatus(302); // 正常処理
});

test('2-6-13: markEmailAsVerified() 失敗', function () {
    $user = User::factory()->unverified()->create();

    // markEmailAsVerified() メソッドが QueryException を投げるようにモック
    $userMock = \Mockery::mock($user)->makePartial();
    $userMock->shouldReceive('markEmailAsVerified')
        ->andThrow(new \Illuminate\Database\QueryException('mysql', 'UPDATE users SET email_verified_at = ? WHERE id = ?', [], new \Exception('Database error')));

    // リクエストのユーザーをモックに置き換え
    $this->actingAs($userMock);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->get($verificationUrl);

    // データベースエラーの場合は database_error エラータイプ
    $response->assertRedirect();
    $response->assertRedirectContains('/email/verify?error=database_error');

    // モックのクリア
    \Mockery::close();
});

test('2-6-14: Verified イベント発火失敗', function () {
    $user = User::factory()->unverified()->create();

    // イベント発火時に例外を発生させる
    Event::listen(Verified::class, function () {
        throw new \Exception('Event dispatch failed');
    });

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    // エラータイプベースのリダイレクト
    $response->assertRedirect();
    $response->assertRedirectContains('/email/verify?error=verification_failed');
});

test('2-6-15: データベース接続エラー', function () {
    $user = User::factory()->unverified()->create();

    // markEmailAsVerified() メソッドがデータベース接続エラーを投げるようにモック
    $userMock = \Mockery::mock($user)->makePartial();
    $userMock->shouldReceive('markEmailAsVerified')
        ->andThrow(new \Illuminate\Database\QueryException('mysql', 'CONNECT', [], new \Exception('Database connection failed')));

    // リクエストのユーザーをモックに置き換え
    $this->actingAs($userMock);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->get($verificationUrl);

    // データベース接続エラーの場合は database_error エラータイプ
    $response->assertRedirect();
    $response->assertRedirectContains('/email/verify?error=database_error');

    // モックのクリア
    \Mockery::close();
});

test('2-6-16: ログ出力とエラーリダイレクト確認', function () {
    $user = User::factory()->unverified()->create();

    // markEmailAsVerified() メソッドが例外を投げるようにモック
    $userMock = \Mockery::mock($user)->makePartial();
    $userMock->shouldReceive('markEmailAsVerified')
        ->andThrow(new \Exception('Test exception for logging'));

    // リクエストのユーザーをモックに置き換え
    $this->actingAs($userMock);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->get($verificationUrl);

    // エラーリダイレクトの確認
    $response->assertRedirect();
    $response->assertRedirectContains('/email/verify?error=verification_failed');

    // モックのクリア
    \Mockery::close();
});

test('2-6-17: エラー時のリダイレクト URL 確認', function () {
    $user = User::factory()->unverified()->create();

    // markEmailAsVerified() メソッドが例外を投げるようにモック
    $userMock = \Mockery::mock($user)->makePartial();
    $userMock->shouldReceive('markEmailAsVerified')
        ->andThrow(new \Exception('Test error message'));

    // リクエストのユーザーをモックに置き換え
    $this->actingAs($userMock);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->get($verificationUrl);

    // エラータイプベースのリダイレクト
    $response->assertRedirect();
    $response->assertRedirectContains('/email/verify?error=verification_failed');

    // モックのクリア
    \Mockery::close();
});

test('2-6-18: フロントエンド URL 設定なし', function () {
    $user = User::factory()->unverified()->create();

    // frontend_url設定をクリア
    Config::set('app.frontend_url', null);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    // 設定がない場合のリダイレクト先を確認
    $response->assertRedirect('/plan?verified=1');
});
