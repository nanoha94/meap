<?php

use App\Models\Color;
use App\Models\Group;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

beforeEach(function () {
    $colors = [
        ['name' => 'イエロー', 'color_code_hex' => '#F5B12E', 'order' => 0],
        ['name' => 'レッド', 'color_code_hex' => '#EC3D33', 'order' => 3],
        ['name' => 'ブルー', 'color_code_hex' => '#2673B8', 'order' => 7],
    ];

    foreach ($colors as $color) {
        Color::create($color);
    }
});

function mockSocialiteUser(array $overrides = []): SocialiteUser
{
    $socialiteUser = new SocialiteUser();
    $socialiteUser->map([
        'id'    => $overrides['id'] ?? 'google-id-123',
        'name'  => array_key_exists('name', $overrides) ? $overrides['name'] : 'Test Google User',
        'email' => array_key_exists('email', $overrides) ? $overrides['email'] : 'google@example.com',
    ]);

    return $socialiteUser;
}

function mockSocialiteRedirect(string $url = 'https://accounts.google.com/o/oauth2/auth'): void
{
    $driver = Mockery::mock(GoogleProvider::class);
    $driver->shouldReceive('redirect')
        ->once()
        ->andReturn(redirect($url));

    Socialite::shouldReceive('driver')
        ->with('google')
        ->once()
        ->andReturn($driver);
}

function mockSocialiteCallback(SocialiteUser $socialiteUser): void
{
    $driver = Mockery::mock(GoogleProvider::class);
    $driver->shouldReceive('user')
        ->once()
        ->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->once()
        ->andReturn($driver);
}

function mockSocialiteCallbackThrows(\Throwable $exception): void
{
    $driver = Mockery::mock(GoogleProvider::class);
    $driver->shouldReceive('user')
        ->once()
        ->andThrow($exception);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->once()
        ->andReturn($driver);
}

// ===== redirectToGoogle() メソッドのテストケース =====

test('2-7-1: 【リダイレクト】 Google OAuth 画面へリダイレクト', function () {
    mockSocialiteRedirect();

    $response = $this->get('/auth/google/redirect');

    $response->assertStatus(302);
    $response->assertRedirectContains('accounts.google.com');
});

test('2-7-2: 【リダイレクト】 認証済みユーザーはリダイレクトできない', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'web')->get('/auth/google/redirect');

    $response->assertStatus(302);
    $this->assertAuthenticated('web');
});

// ===== handleGoogleCallback() メソッドのテストケース =====

test('2-7-3: 【コールバック】 新規ユーザー作成＋ログイン', function () {
    $socialiteUser = mockSocialiteUser([
        'id'    => 'new-google-id',
        'name'  => 'New Google User',
        'email' => 'newuser@example.com',
    ]);
    mockSocialiteCallback($socialiteUser);

    $response = $this->get('/auth/google/callback');

    $response->assertStatus(302);
    $response->assertRedirect(config('app.frontend_url') . '/plan');

    $user = User::where('email', 'newuser@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('New Google User');
    expect($user->email_verified_at)->not->toBeNull();

    $this->assertAuthenticatedAs($user, 'web');

    expect(SocialAccount::where('user_id', $user->id)
        ->where('provider', 'google')
        ->where('provider_id', 'new-google-id')
        ->exists())->toBeTrue();

    expect($user->groups()->count())->toBe(1);
});

test('2-7-4: 【コールバック】 既存 SocialAccount でログイン', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $group = Group::createGroup();
    $group->users()->attach($user->id);
    SocialAccount::create([
        'user_id'     => $user->id,
        'provider'    => 'google',
        'provider_id' => 'existing-google-id',
    ]);

    $socialiteUser = mockSocialiteUser([
        'id'    => 'existing-google-id',
        'email' => $user->email,
    ]);
    mockSocialiteCallback($socialiteUser);

    $response = $this->get('/auth/google/callback');

    $response->assertStatus(302);
    $response->assertRedirect(config('app.frontend_url') . '/plan');
    $this->assertAuthenticatedAs($user, 'web');

    expect(SocialAccount::where('provider', 'google')->count())->toBe(1);
});

test('2-7-5: 【コールバック】 同一メール既存ユーザーに SocialAccount を紐付けてログイン', function () {
    $user = User::factory()->create([
        'email'             => 'existing@example.com',
        'email_verified_at' => now(),
    ]);
    $group = Group::createGroup();
    $group->users()->attach($user->id);

    $socialiteUser = mockSocialiteUser([
        'id'    => 'link-google-id',
        'email' => 'existing@example.com',
    ]);
    mockSocialiteCallback($socialiteUser);

    $response = $this->get('/auth/google/callback');

    $response->assertStatus(302);
    $response->assertRedirect(config('app.frontend_url') . '/plan');
    $this->assertAuthenticatedAs($user, 'web');

    expect(SocialAccount::where('user_id', $user->id)
        ->where('provider', 'google')
        ->where('provider_id', 'link-google-id')
        ->exists())->toBeTrue();
});

test('2-7-6: 【コールバック】 メール未確認の既存ユーザーに紐付け時 email_verified_at を設定', function () {
    $user = User::factory()->create([
        'email'             => 'unverified@example.com',
        'email_verified_at' => null,
    ]);
    $group = Group::createGroup();
    $group->users()->attach($user->id);

    $socialiteUser = mockSocialiteUser([
        'id'    => 'verify-google-id',
        'email' => 'unverified@example.com',
    ]);
    mockSocialiteCallback($socialiteUser);

    $response = $this->get('/auth/google/callback');

    $response->assertStatus(302);
    $this->assertAuthenticatedAs($user, 'web');

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull();
});

test('2-7-7: 【コールバック】 Google から名前が空の場合メールのローカルパートを使用', function () {
    $socialiteUser = mockSocialiteUser([
        'id'    => 'noname-google-id',
        'name'  => '',
        'email' => 'localpart@example.com',
    ]);
    mockSocialiteCallback($socialiteUser);

    $response = $this->get('/auth/google/callback');

    $response->assertStatus(302);

    $user = User::where('email', 'localpart@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('localpart');
});

test('2-7-8: 【コールバック】 セッション再生成の確認', function () {
    $socialiteUser = mockSocialiteUser([
        'id'    => 'session-google-id',
        'email' => 'session@example.com',
    ]);
    mockSocialiteCallback($socialiteUser);

    $oldSessionId = session()->getId();

    $response = $this->get('/auth/google/callback');

    $response->assertStatus(302);
    $this->assertAuthenticated('web');

    expect(session()->getId())->not->toBe($oldSessionId);
});

test('2-7-9: 【コールバック】 InvalidStateException でエラーリダイレクト', function () {
    mockSocialiteCallbackThrows(new InvalidStateException());

    $response = $this->get('/auth/google/callback');

    $response->assertStatus(302);
    $response->assertRedirect(config('app.frontend_url') . '/login?error=oauth_state_invalid');
    $this->assertGuest();
});

test('2-7-10: 【コールバック】 Google API エラーでエラーリダイレクト', function () {
    mockSocialiteCallbackThrows(new \RuntimeException('Google API error'));

    $response = $this->get('/auth/google/callback');

    $response->assertStatus(302);
    $response->assertRedirect(config('app.frontend_url') . '/login?error=oauth_failed');
    $this->assertGuest();
});

test('2-7-11: 【コールバック】 Google からメールが返らない場合エラーリダイレクト', function () {
    $socialiteUser = mockSocialiteUser([
        'id'    => 'noemail-google-id',
        'email' => null,
    ]);
    mockSocialiteCallback($socialiteUser);

    $response = $this->get('/auth/google/callback');

    $response->assertStatus(302);
    $response->assertRedirect(config('app.frontend_url') . '/login?error=oauth_no_email');
    $this->assertGuest();
});
