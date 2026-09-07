<?php

use App\Models\InvitationToken;
use App\Models\User;
use App\Services\InvitationTokenService;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function createInvitationTokenForCleanupTest(string $inviterUserId, string $plainToken, Carbon $expiresAt): InvitationToken
{
    return InvitationToken::create([
        'inviter_user_id' => $inviterUserId,
        'token' => Hash::make($plainToken),
        'token_lookup' => InvitationTokenService::extractTokenLookup($plainToken),
        'expires_at' => $expiresAt,
    ]);
}

// ===== 期限切れトークンの日次削除 =====

test('8-1-1: 【期限切れ削除】 日次スケジュールで期限切れトークンのみ削除される', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $expiredPlainToken = 'expired-token-aaaaaaaaaaaaaaaa';
    $validPlainToken = 'valid-token-bbbbbbbbbbbbbbbbbb';

    createInvitationTokenForCleanupTest($user->id, $expiredPlainToken, Carbon::now()->subHour());
    createInvitationTokenForCleanupTest($user->id, $validPlainToken, Carbon::now()->addHour());

    $event = collect(app(Schedule::class)->events())
        ->first(fn (Event $event) => $event->description === 'Delete expired invitation tokens');

    expect($event)->not->toBeNull();
    expect($event->expression)->toBe('0 0 * * *');

    $event->run(app());

    $invitationTokenService = app(InvitationTokenService::class);

    expect(InvitationToken::count())->toBe(1);
    expect($invitationTokenService->findByPlainToken($expiredPlainToken))->toBeNull();
    expect($invitationTokenService->findByPlainToken($validPlainToken))->not->toBeNull();
});
