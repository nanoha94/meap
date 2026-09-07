<?php

use App\Models\InvitationToken;
use App\Models\User;
use App\Services\InvitationTokenService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(InvitationTokenService::class);
    $this->user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
});

function createInvitationTokenForServiceTest(string $inviterUserId, string $plainToken, Carbon $expiresAt): InvitationToken
{
    return InvitationToken::create([
        'inviter_user_id' => $inviterUserId,
        'token' => Hash::make($plainToken),
        'token_lookup' => InvitationTokenService::extractTokenLookup($plainToken),
        'expires_at' => $expiresAt,
    ]);
}

// ===== findByPlainToken() メソッドのテストケース =====

test('4-7-1: 【トークンルックアップ】 平文トークンに一致するレコードを返す', function () {
    $plainToken = 'abcdefgh' . str_repeat('a', 24);
    createInvitationTokenForServiceTest($this->user->id, $plainToken, Carbon::now()->addHour());

    $found = $this->service->findByPlainToken($plainToken);

    expect($found)->not->toBeNull();
    expect($found->inviter_user_id)->toBe($this->user->id);
});

test('4-7-2: 【トークンルックアップ】 存在しないトークンは null', function () {
    $plainToken = 'abcdefgh' . str_repeat('z', 24);
    createInvitationTokenForServiceTest($this->user->id, 'abcdefgh' . str_repeat('a', 24), Carbon::now()->addHour());

    expect($this->service->findByPlainToken($plainToken))->toBeNull();
});

test('4-7-3: 【トークンルックアップ】 同一 prefix でハッシュ不一致は null', function () {
    $storedToken = 'abcdefgh' . str_repeat('a', 24);
    $otherToken = 'abcdefgh' . str_repeat('b', 24);
    createInvitationTokenForServiceTest($this->user->id, $storedToken, Carbon::now()->addHour());

    expect($this->service->findByPlainToken($otherToken))->toBeNull();
});

// ===== createWithExpiration() メソッドのテストケース =====

test('4-7-4: 【トークン生成】 既存トークンと衝突時に再試行して保存する', function () {
    $duplicateToken = 'duplicate-token-for-retry-test';
    createInvitationTokenForServiceTest($this->user->id, $duplicateToken, Carbon::now()->addHour());

    $generatedTokens = [$duplicateToken, 'unique-token-after-retry-test01'];
    $this->mock(InvitationTokenService::class, function ($mock) use ($generatedTokens) {
        $mock->makePartial();
        $mock->shouldReceive('generateToken')
            ->twice()
            ->andReturn($generatedTokens[0], $generatedTokens[1]);
    });

    $service = app(InvitationTokenService::class);
    $token = $service->createWithExpiration($this->user->id, Carbon::now()->addHour());

    expect($token)->toBe('unique-token-after-retry-test01');
    expect(InvitationToken::count())->toBe(2);
    expect($service->findByPlainToken($token))->not->toBeNull();
});
