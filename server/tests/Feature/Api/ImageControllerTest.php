<?php

use App\Models\User;
use App\Models\Group;
use App\Models\Image;
use App\Models\Color;
use App\Models\Recipe;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Colorマスターデータをシード
    $colors = [
        ['name' => 'イエロー', 'color_code_hex' => '#F5B12E', 'order' => 0],
        ['name' => 'レッド', 'color_code_hex' => '#EC3D33', 'order' => 3],
        ['name' => 'ブルー', 'color_code_hex' => '#2673B8', 'order' => 7],
    ];

    foreach ($colors as $color) {
        Color::create($color);
    }

    // ストレージをフェイクに設定
    Storage::fake('public');
});

// ===== bulkUpload() メソッドのテストケース =====

test('3-1-1: 【一括アップロード】 正常な画像アップロード（1 枚）', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '画像を1件アップロードしました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'src',
                'width',
                'height'
            ]
        ]
    ]);

    // データの内容確認
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0])->toHaveKeys(['id', 'src', 'width', 'height']);
    expect($data[0]['width'])->toBe(100);
    expect($data[0]['height'])->toBe(100);

    // データベースに画像が保存されていることを確認
    $this->assertDatabaseHas('images', [
        'width' => 100,
        'height' => 100
    ]);

    // srcが正しい形式で保存されていることを確認
    $image = Image::where('width', 100)->where('height', 100)->first();
    expect($image->src)->toContain("/storage/images/{$group->id}/");
});

test('3-1-2: 【一括アップロード】 複数画像の一括アップロード', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $files = [
        UploadedFile::fake()->image('test1.jpg', 100, 100),
        UploadedFile::fake()->image('test2.jpg', 100, 100),
        UploadedFile::fake()->image('test3.jpg', 100, 100)
    ];

    $response = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => $files
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '画像を3件アップロードしました。'
    ]);

    // 3つの画像がデータベースに保存されていることを確認
    $this->assertDatabaseCount('images', 3);
});

test('3-1-3: 【一括アップロード】 グループID配下に直接保存されることを確認', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);

    $response->assertStatus(200);

    // 画像が保存されていることを確認
    $this->assertDatabaseHas('images', [
        'width' => 100,
        'height' => 100
    ]);

    // srcがグループID配下に直接保存されていることを確認（ディレクトリ分けなし）
    $image = Image::where('width', 100)->where('height', 100)->first();
    expect($image->src)->toContain("/storage/images/{$group->id}/");
    // ディレクトリ名が含まれていないことを確認
    expect($image->src)->not->toContain("/storage/images/{$group->id}/general/");
    expect($image->src)->not->toContain("/storage/images/{$group->id}/recipes/");
});

test('3-1-4: 【一括アップロード】 未認証ユーザー', function () {
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->post('/images/upload-bulk', [
        'images' => [$file]
    ]);

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。'
    ]);
});

test('3-1-5: 【一括アップロード】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。'
    ]);
});

test('3-1-6: 【一括アップロード】 データベース接続エラー', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // データベース接続をモックしてエラーを発生させる
    DB::shouldReceive('beginTransaction')
        ->andThrow(new \Exception('Database connection error'));

    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false
    ]);
});

test('3-1-7: 【一括アップロード】 ImageService 例外', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // ImageServiceのメソッドで例外を発生させる
    $this->mock(ImageService::class, function ($mock) {
        $mock->shouldReceive('getValidImageFiles')
            ->andThrow(new \Exception('ImageService error'));
    });

    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false
    ]);
});

test('3-1-8: 【一括アップロード】 ファイルアップロード失敗', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // ストレージの書き込みを失敗させる
    Storage::shouldReceive('disk')
        ->with('public')
        ->andReturnSelf();
    Storage::shouldReceive('put')
        ->andThrow(new \Exception('File upload failed'));

    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false
    ]);
});

// ===== bulkUpload() メソッドのテストケース =====

test('3-1-9: 【一括アップロード】 バリデーションエラー（ファイルサイズ制限）', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // 10MBを超えるファイルを作成
    $file = UploadedFile::fake()->create('large.jpg', 11000); // 11MB

    $response = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['images.0']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('images.*には、10240 kb以下のファイルを指定してください。', $responseData['errors']['images.0']);
});

test('3-1-10: 【一括アップロード】 バリデーションエラー（最大ファイル数制限）', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // 21個のファイルを作成（制限は20個）
    $files = [];
    for ($i = 0; $i < 21; $i++) {
        $files[] = UploadedFile::fake()->image("test{$i}.jpg", 100, 100);
    }

    $response = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => $files
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['images']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('imagesは20個以下指定してください。', $responseData['errors']['images']);
});

test('3-1-11: 【一括アップロード】 バリデーションエラー（最小ファイル数制限）', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $response = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => []
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['images']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('imagesは必ず指定してください。', $responseData['errors']['images']);
});

test('3-1-12: 【一括アップロード】 バリデーションエラー（ファイル配列バリデーション）', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $response = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => 'not_an_array'
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['images']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('imagesは配列でなくてはなりません。', $responseData['errors']['images']);
});

// ===== bulkDestroy() メソッドのテストケース =====

test('3-1-13: 【一括削除】 正常な画像削除（1 枚）', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // 画像をアップロードAPIで作成
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);
    $uploadResponse = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);
    $imageId = $uploadResponse->json('data.0.id');
    $relatedId = \Illuminate\Support\Str::uuid()->toString();

    $response = $this->actingAs($user)->delete('/images/bulk', [
        'ids' => [$imageId],
        'related_id' => $relatedId
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '画像を1件削除しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);

    // データベースから画像が削除されていることを確認
    $this->assertDatabaseMissing('images', [
        'id' => $imageId
    ]);
});

test('3-1-14: 【一括削除】 複数画像の一括削除', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // 複数の画像をアップロードAPIで作成
    $files = [
        UploadedFile::fake()->image('test1.jpg', 100, 100),
        UploadedFile::fake()->image('test2.jpg', 100, 100),
        UploadedFile::fake()->image('test3.jpg', 100, 100)
    ];
    $uploadResponse = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => $files
    ]);
    $imageIds = collect($uploadResponse->json('data'))->pluck('id')->toArray();
    $relatedId = \Illuminate\Support\Str::uuid()->toString();

    $response = $this->actingAs($user)->delete('/images/bulk', [
        'ids' => $imageIds,
        'related_id' => $relatedId
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '画像を3件削除しました。'
    ]);

    // 全ての画像がデータベースから削除されていることを確認
    foreach ($imageIds as $id) {
        $this->assertDatabaseMissing('images', ['id' => $id]);
    }
});

test('3-1-15: 【一括削除】 削除成功メッセージの確認', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // 画像をアップロードAPIで作成
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);
    $uploadResponse = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);
    $imageId = $uploadResponse->json('data.0.id');
    $relatedId = \Illuminate\Support\Str::uuid()->toString();

    $response = $this->actingAs($user)->delete('/images/bulk', [
        'ids' => [$imageId],
        'related_id' => $relatedId
    ]);

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toBe('画像を1件削除しました。');
});

test('3-1-20: 【一括削除】 未認証ユーザー', function () {
    $response = $this->delete('/images/bulk', [
        'ids' => [\Illuminate\Support\Str::uuid()],
        'related_id' => \Illuminate\Support\Str::uuid()
    ]);

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。'
    ]);
});

test('3-1-21: 【一括削除】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    // デバッグ: ユーザーのグループを確認
    $userGroup = $user->groups()->first();
    expect($userGroup)->toBeNull();

    // 任意の画像IDを使用（実際には検証前にエラーになるため）
    $response = $this->actingAs($user)->delete('/images/bulk', [
        'ids' => [\Illuminate\Support\Str::uuid()],
        'related_id' => \Illuminate\Support\Str::uuid()
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'ユーザーはグループに所属していません。'
    ]);
});

test('3-1-22: 【一括削除】 データベース接続エラー', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // 画像をアップロードAPIで作成
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);
    $uploadResponse = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);
    $imageId = $uploadResponse->json('data.0.id');
    $relatedId = \Illuminate\Support\Str::uuid()->toString();

    // データベース接続をモックしてエラーを発生させる
    DB::shouldReceive('beginTransaction')
        ->andThrow(new \Exception('Database connection error'));

    $response = $this->actingAs($user)->delete('/images/bulk', [
        'ids' => [$imageId],
        'related_id' => $relatedId
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false
    ]);
});

test('3-1-23: 【一括削除】 ImageService 例外', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // 画像をアップロードAPIで作成
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);
    $uploadResponse = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);
    $imageId = $uploadResponse->json('data.0.id');
    $relatedId = \Illuminate\Support\Str::uuid()->toString();

    // ImageServiceのメソッドで例外を発生させる
    $this->mock(ImageService::class, function ($mock) {
        $mock->shouldReceive('deleteImages')
            ->andThrow(new \Exception('ImageService error'));
    });

    $response = $this->actingAs($user)->delete('/images/bulk', [
        'ids' => [$imageId],
        'related_id' => $relatedId
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false
    ]);
});

test('3-1-24: 【一括削除】 ファイル削除失敗', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // 画像をアップロードAPIで作成
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);
    $uploadResponse = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);
    $imageId = $uploadResponse->json('data.0.id');
    $relatedId = \Illuminate\Support\Str::uuid()->toString();

    // ストレージの削除を失敗させる
    Storage::shouldReceive('disk')
        ->with('public')
        ->andReturnSelf();
    Storage::shouldReceive('exists')
        ->andReturn(true);
    Storage::shouldReceive('delete')
        ->andThrow(new \Exception('File delete failed'));

    $response = $this->actingAs($user)->delete('/images/bulk', [
        'ids' => [$imageId],
        'related_id' => $relatedId
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false
    ]);
});

// ===== bulkDestroy() メソッドのテストケース =====

test('3-1-16: 【一括削除】 存在しない画像 ID の削除', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $response = $this->actingAs($user)->delete('/images/bulk', [
        'ids' => [\Illuminate\Support\Str::uuid()->toString()], // 存在しないID（文字列に変換）
        'related_id' => \Illuminate\Support\Str::uuid()->toString()
    ]);

    // 存在しない画像IDを指定した場合、削除数0で正常終了する
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '画像を0件削除しました。'
    ]);

    // レスポンス構造の確認
    $response->assertJsonStructure([
        'success',
        'message'
    ]);
});

test('3-1-25: 【一括削除】 バリデーションエラー（削除 ID 配列バリデーション）', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $response = $this->actingAs($user)->delete('/images/bulk', [
        'ids' => 'not_an_array',
        'related_id' => \Illuminate\Support\Str::uuid()->toString()
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ids']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('idsは配列でなくてはなりません。', $responseData['errors']['ids']);
});

test('3-1-26: 【一括削除】 バリデーションエラー（削除 ID 最小数制限）', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $response = $this->actingAs($user)->delete('/images/bulk', [
        'ids' => [],
        'related_id' => \Illuminate\Support\Str::uuid()->toString()
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ids']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('idsは必ず指定してください。', $responseData['errors']['ids']);
});

test('3-1-27: 【一括削除】 バリデーションエラー（削除 ID UUID 形式バリデーション）', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $response = $this->actingAs($user)->delete('/images/bulk', [
        'ids' => ['invalid-uuid-format'],
        'related_id' => \Illuminate\Support\Str::uuid()->toString()
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ids.0']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('ids.*に有効なUUIDを指定してください。', $responseData['errors']['ids.0']);
});

test('3-1-28: 【一括削除】 バリデーションエラー（related_id 必須項目）', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $response = $this->actingAs($user)->delete('/images/bulk', [
        'ids' => [\Illuminate\Support\Str::uuid()->toString()]
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['related_id']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('related_idは必ず指定してください。', $responseData['errors']['related_id']);
});

test('3-1-29: 【一括削除】 バリデーションエラー（related_id UUID 形式）', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    $response = $this->actingAs($user)->delete('/images/bulk', [
        'ids' => [\Illuminate\Support\Str::uuid()->toString()],
        'related_id' => 'invalid-uuid-format'
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['related_id']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('related_idに有効なUUIDを指定してください。', $responseData['errors']['related_id']);
});

// ===== bulkDestroy() メソッドのテストケース =====

test('3-1-17: 【一括削除】 指定した related_id との紐づけのみを解除', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // 画像をアップロードAPIで作成
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);
    $uploadResponse = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);
    $imageId = $uploadResponse->json('data.0.id');

    // image_mappingsに複数の紐づけを作成
    $relatedId1 = \Illuminate\Support\Str::uuid()->toString();
    $relatedId2 = \Illuminate\Support\Str::uuid()->toString();

    // 1つ目の紐づけ
    DB::table('image_mappings')->insert([
        'image_id' => $imageId,
        'group_id' => $group->id,
        'related_model' => Recipe::class,
        'related_id' => $relatedId1,
        'image_type' => 'thumbnail',
        'order' => 0
    ]);

    // 2つ目の紐づけ
    DB::table('image_mappings')->insert([
        'image_id' => $imageId,
        'group_id' => $group->id,
        'related_model' => Recipe::class,
        'related_id' => $relatedId2,
        'image_type' => 'thumbnail',
        'order' => 0
    ]);

    // relatedId1との紐づけのみを解除
    $response = $this->actingAs($user)->delete('/images/bulk', [
        'ids' => [$imageId],
        'related_id' => $relatedId1
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '画像を0件削除しました。'
    ]);

    // データベースに画像が残っていることを確認（画像レコードは削除されない）
    $this->assertDatabaseHas('images', [
        'id' => $imageId
    ]);

    // relatedId1の紐づけは削除されていることを確認
    $mapping1Count = DB::table('image_mappings')
        ->where('image_id', $imageId)
        ->where('related_id', $relatedId1)
        ->count();
    expect($mapping1Count)->toBe(0);

    // relatedId2の紐づけは残っていることを確認
    $mapping2Count = DB::table('image_mappings')
        ->where('image_id', $imageId)
        ->where('related_id', $relatedId2)
        ->count();
    expect($mapping2Count)->toBe(1);
});

test('3-1-18: 【一括削除】 紐づけ解除後、他の紐づけがなければ物理削除', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // 画像をアップロードAPIで作成
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);
    $uploadResponse = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);
    $imageId = $uploadResponse->json('data.0.id');
    $relatedId = \Illuminate\Support\Str::uuid()->toString();

    // image_mappingsに紐づけを作成（1つのみ）
    DB::table('image_mappings')->insert([
        'image_id' => $imageId,
        'group_id' => $group->id,
        'related_model' => Recipe::class,
        'related_id' => $relatedId,
        'image_type' => 'thumbnail',
        'order' => 0
    ]);

    // 紐づけを解除（他の紐づけがないため、物理削除も実行される）
    $response = $this->actingAs($user)->delete('/images/bulk', [
        'ids' => [$imageId],
        'related_id' => $relatedId
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '画像を1件削除しました。'
    ]);

    // データベースから画像が削除されていることを確認
    $this->assertDatabaseMissing('images', [
        'id' => $imageId
    ]);

    // image_mappingsも削除されていることを確認
    $mappingCount = DB::table('image_mappings')
        ->where('image_id', $imageId)
        ->count();
    expect($mappingCount)->toBe(0);
});

test('3-1-19: 【一括削除】 複数の画像で一部は物理削除、一部は紐づけ解除のみ', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    $group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $user->id,
        'group_id' => $group->id
    ]);

    // 2つの画像をアップロードAPIで作成
    $files = [
        UploadedFile::fake()->image('test1.jpg', 100, 100),
        UploadedFile::fake()->image('test2.jpg', 100, 100)
    ];
    $uploadResponse = $this->actingAs($user)->post('/images/upload-bulk', [
        'images' => $files
    ]);
    $imageIds = collect($uploadResponse->json('data'))->pluck('id')->toArray();
    $imageId1 = $imageIds[0]; // 他の紐づけがない画像
    $imageId2 = $imageIds[1]; // 他の紐づけがある画像

    $relatedId1 = \Illuminate\Support\Str::uuid()->toString();
    $relatedId2 = \Illuminate\Support\Str::uuid()->toString();
    $relatedId3 = \Illuminate\Support\Str::uuid()->toString();

    // imageId1に1つの紐づけのみ作成
    DB::table('image_mappings')->insert([
        'image_id' => $imageId1,
        'group_id' => $group->id,
        'related_model' => Recipe::class,
        'related_id' => $relatedId1,
        'image_type' => 'thumbnail',
        'order' => 0
    ]);

    // imageId2に2つの紐づけを作成
    DB::table('image_mappings')->insert([
        'image_id' => $imageId2,
        'group_id' => $group->id,
        'related_model' => Recipe::class,
        'related_id' => $relatedId2,
        'image_type' => 'thumbnail',
        'order' => 0
    ]);
    DB::table('image_mappings')->insert([
        'image_id' => $imageId2,
        'group_id' => $group->id,
        'related_model' => Recipe::class,
        'related_id' => $relatedId3,
        'image_type' => 'thumbnail',
        'order' => 0
    ]);

    // 両方の画像のrelatedId1との紐づけを解除
    $response = $this->actingAs($user)->delete('/images/bulk', [
        'ids' => $imageIds,
        'related_id' => $relatedId1
    ]);

    $response->assertStatus(200);
    // imageId1は物理削除、imageId2は紐づけ解除のみ
    $response->assertJson([
        'success' => true,
        'message' => '画像を1件削除しました。'
    ]);

    // imageId1は削除されていることを確認（物理削除）
    $this->assertDatabaseMissing('images', [
        'id' => $imageId1
    ]);

    // imageId2は残っていることを確認（紐づけ解除のみ）
    $this->assertDatabaseHas('images', [
        'id' => $imageId2
    ]);

    // imageId2のrelatedId2の紐づけは残っていることを確認
    $mapping2Count = DB::table('image_mappings')
        ->where('image_id', $imageId2)
        ->where('related_id', $relatedId2)
        ->count();
    expect($mapping2Count)->toBe(1);

    // imageId2のrelatedId3の紐づけは残っていることを確認
    $mapping3Count = DB::table('image_mappings')
        ->where('image_id', $imageId2)
        ->where('related_id', $relatedId3)
        ->count();
    expect($mapping3Count)->toBe(1);
});
