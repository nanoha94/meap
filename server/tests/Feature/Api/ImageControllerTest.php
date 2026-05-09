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

    // テスト用のユーザーとグループを作成
    $this->user = User::factory()->create([
        'email_verified_at' => now()
    ]);

    $this->group = Group::create([
        'group_size' => 1
    ]);

    DB::table('group_user_mappings')->insert([
        'user_id' => $this->user->id,
        'group_id' => $this->group->id
    ]);

    // ユーザーとグループの関係をリフレッシュ
    $this->user->refresh();
    $this->user->load('groups');
});

// ===== bulkUpload() メソッドのテストケース =====

test('3-1-1: 【一括アップロード】 正常な画像アップロード（1 枚）', function () {
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->actingAs($this->user)->post('/images/upload-bulk', [
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
    expect($image->src)->toContain("/storage/images/groups/{$this->group->id}/");
});

test('3-1-2: 【一括アップロード】 複数画像の一括アップロード', function () {
    $files = [
        UploadedFile::fake()->image('test1.jpg', 100, 100),
        UploadedFile::fake()->image('test2.jpg', 100, 100),
        UploadedFile::fake()->image('test3.jpg', 100, 100)
    ];

    $response = $this->actingAs($this->user)->post('/images/upload-bulk', [
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

test('3-1-3: 【一括アップロード】 グループ ID 配下に直接保存されることを確認', function () {
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->actingAs($this->user)->post('/images/upload-bulk', [
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
    expect($image->src)->toContain("/storage/images/groups/{$this->group->id}/");
    // ディレクトリ名が含まれていないことを確認
    expect($image->src)->not->toContain("/storage/images/groups/{$this->group->id}/general/");
    expect($image->src)->not->toContain("/storage/images/groups/{$this->group->id}/recipes/");
});

test('3-1-4: 【一括アップロード】 upload_path 指定時に指定パスに保存される', function () {
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => [$file],
        'upload_path' => 'users'
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '画像を1件アップロードしました。'
    ]);

    // srcが指定パスに保存されていることを確認
    $image = Image::where('width', 100)->where('height', 100)->first();
    expect($image->src)->toContain('/storage/images/users/');
    // グループID配下ではないことを確認
    expect($image->src)->not->toContain("/storage/images/groups/{$this->group->id}/");
});

test('3-1-5: 【一括アップロード】 upload_path 未指定時にグループ ID 配下に保存される', function () {
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => [$file]
        // upload_path を指定しない
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '画像を1件アップロードしました。'
    ]);

    // srcがグループID配下に保存されていることを確認（従来動作）
    $image = Image::where('width', 100)->where('height', 100)->first();
    expect($image->src)->toContain("/storage/images/groups/{$this->group->id}/");
});

test('3-1-6: 【一括アップロード】 アップロードした画像から Exif が削除される', function () {
    $fixturePath = base_path('tests/fixtures/exif-sample.jpg');
    expect(file_exists($fixturePath))->toBeTrue('tests/fixtures/exif-sample.jpg を配置してください。');

    $fixtureExif = @exif_read_data($fixturePath);
    expect($fixtureExif)->not->toBeFalse();
    expect($fixtureExif)->toBeArray();
    expect($fixtureExif)->not->toBe([]);

    $file = new UploadedFile(
        $fixturePath,
        'exif-sample.jpg',
        'image/jpeg',
        null,
        true
    );

    $response = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);

    $response->assertStatus(200);

    $dir = "images/groups/{$this->group->id}";
    $stored = Storage::disk('public')->files($dir);
    expect($stored)->toHaveCount(1);

    $bin = Storage::disk('public')->get($stored[0]);
    $tmp = tempnam(sys_get_temp_dir(), 'jpeg');
    expect($tmp)->not->toBeFalse();
    file_put_contents($tmp, $bin);
    $strippedExif = null;
    $strippedExifSegment = null;
    try {
        $strippedExif = @exif_read_data($tmp);
        $strippedExifSegment = @exif_read_data($tmp, 'EXIF');
    } finally {
        @unlink($tmp);
    }

    // GD が COM などを書き込むため exif_read_data は配列を返すことがあるが、
    // カメラ由来の IFD・EXIF サブセクションは再エンコード後は存在しないこと。
    $hadExifSections = isset($fixtureExif['SectionsFound'])
        && str_contains((string) $fixtureExif['SectionsFound'], 'EXIF');
    expect(
        isset($fixtureExif['Orientation']) || isset($fixtureExif['GPS']) || $hadExifSections
    )->toBeTrue('フィクスチャに Orientation / GPS / EXIF セクションのいずれかが含まれること');

    expect($strippedExif)->toBeArray();
    expect(isset($strippedExif['Orientation']))->toBeFalse();
    expect(isset($strippedExif['GPS']))->toBeFalse();
    expect(isset($strippedExif['EXIF']))->toBeFalse();
    if (isset($strippedExif['SectionsFound'])) {
        expect(str_contains((string) $strippedExif['SectionsFound'], 'EXIF'))->toBeFalse();
    }
    expect($strippedExifSegment === false || $strippedExifSegment === [])->toBeTrue();
});

test('3-1-7: 【一括アップロード】 長辺 2000px を超える画像は 2000px に縮小される', function () {
    $file = UploadedFile::fake()->image('test.jpg', 3000, 1500);

    $response = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);

    $response->assertStatus(200);

    $data = $response->json('data');
    expect($data[0]['width'])->toBe(2000);
    expect($data[0]['height'])->toBe(1000);

    $this->assertDatabaseHas('images', [
        'width' => 2000,
        'height' => 1000,
    ]);
});

test('3-1-8: 【一括アップロード】 長辺 2000px 以下の画像はそのままのサイズで保存される', function () {
    $file = UploadedFile::fake()->image('test.jpg', 1000, 500);

    $response = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);

    $response->assertStatus(200);

    $data = $response->json('data');
    expect($data[0]['width'])->toBe(1000);
    expect($data[0]['height'])->toBe(500);

    $this->assertDatabaseHas('images', [
        'width' => 1000,
        'height' => 500,
    ]);
});

test('3-1-9: 【一括アップロード】 PNG / WebP もリサイズと再保存ができる', function () {
    expect(function_exists('imagewebp'))
        ->toBeTrue('GD の WebP サポートが必要です（3-1-9 の WebP 検証）。');

    $formats = [
        ['ext' => 'png', 'type' => IMAGETYPE_PNG],
        ['ext' => 'webp', 'type' => IMAGETYPE_WEBP],
    ];

    foreach ($formats as $format) {
        $file = UploadedFile::fake()->image("test.{$format['ext']}", 3000, 1500);

        $response = $this->actingAs($this->user)->post('/images/upload-bulk', [
            'images' => [$file]
        ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data[0]['width'])->toBe(2000);
        expect($data[0]['height'])->toBe(1000);

        $dir = "images/groups/{$this->group->id}";
        $paths = Storage::disk('public')->files($dir);
        $path = collect($paths)->first(fn ($p) => str_ends_with($p, '.' . $format['ext']));
        expect($path)->not->toBeNull();

        $bin = Storage::disk('public')->get($path);
        $info = @getimagesizefromstring($bin);
        expect($info)->not->toBeFalse();
        expect($info[2])->toBe($format['type']);
    }
});

test('3-1-10: 【一括アップロード】 未認証ユーザー', function () {
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

test('3-1-11: 【一括アップロード】 バリデーションエラー（ファイル配列バリデーション）', function () {
    $response = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => 'not_an_array'
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['images']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('imagesは配列でなくてはなりません。', $responseData['errors']['images']);
});

test('3-1-12: 【一括アップロード】 バリデーションエラー（最小ファイル数制限）', function () {
    $response = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => []
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['images']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('imagesは必ず指定してください。', $responseData['errors']['images']);
});

test('3-1-13: 【一括アップロード】 バリデーションエラー（最大ファイル数制限）', function () {
    // 21個のファイルを作成（制限は20個）
    $files = [];
    for ($i = 0; $i < 21; $i++) {
        $files[] = UploadedFile::fake()->image("test{$i}.jpg", 100, 100);
    }

    $response = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => $files
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['images']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('imagesは20個以下指定してください。', $responseData['errors']['images']);
});

test('3-1-14: 【一括アップロード】 バリデーションエラー（ファイルサイズ制限）', function () {
    // 10MBを超えるファイルを作成
    $file = UploadedFile::fake()->create('large.jpg', 11000); // 11MB

    $response = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['images.0']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('images.*には、10240 kb以下のファイルを指定してください。', $responseData['errors']['images.0']);
});

test('3-1-15: 【一括アップロード】 バリデーションエラー（upload_path パストラバーサル）', function () {
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => [$file],
        'upload_path' => '../etc'
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['upload_path']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('アップロードパスに「..」を含めることはできません。', $responseData['errors']['upload_path']);
});

test('3-1-16: 【一括アップロード】 バリデーションエラー（upload_path 最大文字数超過）', function () {
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    // 256文字のパスを作成（制限は255文字）
    $longPath = str_repeat('a', 256);

    $response = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => [$file],
        'upload_path' => $longPath
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['upload_path']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('upload_pathは、255文字以内で指定してください。', $responseData['errors']['upload_path']);
});

test('3-1-17: 【一括アップロード】 グループが存在しない', function () {
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

test('3-1-18: 【一括アップロード】 ImageService 例外（データベースエラー）', function () {
    // ImageServiceのメソッドで例外を発生させる
    $this->mock(ImageService::class, function ($mock) {
        $mock->shouldReceive('getValidImageFiles')
            ->andThrow(new \Exception('Database connection error'));
    });

    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false
    ]);
});

test('3-1-19: 【一括アップロード】 ImageService 例外', function () {
    // ImageServiceのメソッドで例外を発生させる
    $this->mock(ImageService::class, function ($mock) {
        $mock->shouldReceive('getValidImageFiles')
            ->andThrow(new \Exception('ImageService error'));
    });

    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false
    ]);
});

test('3-1-20: 【一括アップロード】 ファイルアップロード失敗', function () {
    // ストレージの書き込みを失敗させる
    Storage::shouldReceive('disk')
        ->with('public')
        ->andReturnSelf();
    Storage::shouldReceive('put')
        ->andThrow(new \Exception('File upload failed'));

    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false
    ]);
});

// ===== bulkDestroy() メソッドのテストケース =====

test('3-1-21: 【一括削除】 正常な画像削除（1 枚）', function () {
    // 画像をアップロードAPIで作成
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);
    $uploadResponse = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);
    $imageId = $uploadResponse->json('data.0.id');
    $relatedId = \Illuminate\Support\Str::uuid()->toString();

    // image_mappingsに紐づけを作成
    DB::table('image_mappings')->insert([
        'image_id' => $imageId,
        'group_id' => $this->group->id,
        'related_model' => Recipe::class,
        'related_id' => $relatedId,
        'image_type' => 'thumbnail',
        'order' => 0
    ]);

    $response = $this->actingAs($this->user)->delete('/images/bulk', [
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

    // 画像レコードは残ることを確認（紐づけ解除のみ）
    $this->assertDatabaseHas('images', [
        'id' => $imageId
    ]);

    // 紐づけが解除されていることを確認
    $mappingCount = DB::table('image_mappings')
        ->where('image_id', $imageId)
        ->where('related_id', $relatedId)
        ->count();
    expect($mappingCount)->toBe(0);
});

test('3-1-22: 【一括削除】 複数画像の一括削除', function () {
    // 複数の画像をアップロードAPIで作成
    $files = [
        UploadedFile::fake()->image('test1.jpg', 100, 100),
        UploadedFile::fake()->image('test2.jpg', 100, 100),
        UploadedFile::fake()->image('test3.jpg', 100, 100)
    ];
    $uploadResponse = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => $files
    ]);
    $imageIds = collect($uploadResponse->json('data'))->pluck('id')->toArray();
    $relatedId = \Illuminate\Support\Str::uuid()->toString();

    // image_mappingsに紐づけを作成
    foreach ($imageIds as $imageId) {
        DB::table('image_mappings')->insert([
            'image_id' => $imageId,
            'group_id' => $this->group->id,
            'related_model' => Recipe::class,
            'related_id' => $relatedId,
            'image_type' => 'thumbnail',
            'order' => 0
        ]);
    }

    $response = $this->actingAs($this->user)->delete('/images/bulk', [
        'ids' => $imageIds,
        'related_id' => $relatedId
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '画像を3件削除しました。'
    ]);

    // 全ての画像レコードは残ることを確認（紐づけ解除のみ）
    foreach ($imageIds as $id) {
        $this->assertDatabaseHas('images', ['id' => $id]);
    }

    // 全ての紐づけが解除されていることを確認
    foreach ($imageIds as $imageId) {
        $mappingCount = DB::table('image_mappings')
            ->where('image_id', $imageId)
            ->where('related_id', $relatedId)
            ->count();
        expect($mappingCount)->toBe(0);
    }
});

test('3-1-23: 【一括削除】 削除成功メッセージの確認', function () {
    // 画像をアップロードAPIで作成
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);
    $uploadResponse = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);
    $imageId = $uploadResponse->json('data.0.id');
    $relatedId = \Illuminate\Support\Str::uuid()->toString();

    // image_mappingsに紐づけを作成
    DB::table('image_mappings')->insert([
        'image_id' => $imageId,
        'group_id' => $this->group->id,
        'related_model' => Recipe::class,
        'related_id' => $relatedId,
        'image_type' => 'thumbnail',
        'order' => 0
    ]);

    $response = $this->actingAs($this->user)->delete('/images/bulk', [
        'ids' => [$imageId],
        'related_id' => $relatedId
    ]);

    $response->assertStatus(200);

    // メッセージが正しく設定されていることを確認
    $message = $response->json('message');
    expect($message)->toBe('画像を1件削除しました。');
});

test('3-1-24: 【一括削除】 存在しない画像 ID の削除', function () {
    $response = $this->actingAs($this->user)->delete('/images/bulk', [
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

test('3-1-25: 【一括削除】 指定した related_id との紐づけのみを解除', function () {
    // 画像をアップロードAPIで作成
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);
    $uploadResponse = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);
    $imageId = $uploadResponse->json('data.0.id');

    // image_mappingsに複数の紐づけを作成
    $relatedId1 = \Illuminate\Support\Str::uuid()->toString();
    $relatedId2 = \Illuminate\Support\Str::uuid()->toString();

    // 1つ目の紐づけ
    DB::table('image_mappings')->insert([
        'image_id' => $imageId,
        'group_id' => $this->group->id,
        'related_model' => Recipe::class,
        'related_id' => $relatedId1,
        'image_type' => 'thumbnail',
        'order' => 0
    ]);

    // 2つ目の紐づけ
    DB::table('image_mappings')->insert([
        'image_id' => $imageId,
        'group_id' => $this->group->id,
        'related_model' => Recipe::class,
        'related_id' => $relatedId2,
        'image_type' => 'thumbnail',
        'order' => 0
    ]);

    // relatedId1との紐づけのみを解除
    $response = $this->actingAs($this->user)->delete('/images/bulk', [
        'ids' => [$imageId],
        'related_id' => $relatedId1
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '画像を1件削除しました。'
    ]);

    // データベースに画像が残っていることを確認（画像レコードは削除されない）
    $this->assertDatabaseHas('images', [
        'id' => $imageId
    ]);

    // relatedId1の紐づけは解除されていることを確認
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

test('3-1-26: 【一括削除】 紐づけ解除のみ（他の紐づけなしでも物理削除は行わない）', function () {
    // 画像をアップロードAPIで作成
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);
    $uploadResponse = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);
    $imageId = $uploadResponse->json('data.0.id');
    $relatedId = \Illuminate\Support\Str::uuid()->toString();

    // image_mappingsに紐づけを作成（1つのみ）
    DB::table('image_mappings')->insert([
        'image_id' => $imageId,
        'group_id' => $this->group->id,
        'related_model' => Recipe::class,
        'related_id' => $relatedId,
        'image_type' => 'thumbnail',
        'order' => 0
    ]);

    // 紐づけを解除（物理削除は行わない）
    $response = $this->actingAs($this->user)->delete('/images/bulk', [
        'ids' => [$imageId],
        'related_id' => $relatedId
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '画像を1件削除しました。'
    ]);

    // 画像レコードは残ることを確認（紐づけ解除のみ）
    $this->assertDatabaseHas('images', [
        'id' => $imageId
    ]);

    // 紐づけが解除されていることを確認
    $mappingCount = DB::table('image_mappings')
        ->where('image_id', $imageId)
        ->where('related_id', $relatedId)
        ->count();
    expect($mappingCount)->toBe(0);
});

test('3-1-27: 【一括削除】 複数の画像で紐づけ解除のみ', function () {
    // 2つの画像をアップロードAPIで作成
    $files = [
        UploadedFile::fake()->image('test1.jpg', 100, 100),
        UploadedFile::fake()->image('test2.jpg', 100, 100)
    ];
    $uploadResponse = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => $files
    ]);
    $imageIds = collect($uploadResponse->json('data'))->pluck('id')->toArray();
    $imageId1 = $imageIds[0];
    $imageId2 = $imageIds[1];

    $relatedId1 = \Illuminate\Support\Str::uuid()->toString();
    $relatedId2 = \Illuminate\Support\Str::uuid()->toString();
    $relatedId3 = \Illuminate\Support\Str::uuid()->toString();

    // imageId1に1つの紐づけのみ作成
    DB::table('image_mappings')->insert([
        'image_id' => $imageId1,
        'group_id' => $this->group->id,
        'related_model' => Recipe::class,
        'related_id' => $relatedId1,
        'image_type' => 'thumbnail',
        'order' => 0
    ]);

    // imageId2に2つの紐づけを作成
    DB::table('image_mappings')->insert([
        'image_id' => $imageId2,
        'group_id' => $this->group->id,
        'related_model' => Recipe::class,
        'related_id' => $relatedId2,
        'image_type' => 'thumbnail',
        'order' => 0
    ]);
    DB::table('image_mappings')->insert([
        'image_id' => $imageId2,
        'group_id' => $this->group->id,
        'related_model' => Recipe::class,
        'related_id' => $relatedId3,
        'image_type' => 'thumbnail',
        'order' => 0
    ]);

    // 両方の画像のrelatedId1との紐づけを解除
    $response = $this->actingAs($this->user)->delete('/images/bulk', [
        'ids' => $imageIds,
        'related_id' => $relatedId1
    ]);

    $response->assertStatus(200);
    // 両方とも紐づけ解除のみ（物理削除は行わない）
    $response->assertJson([
        'success' => true,
        'message' => '画像を1件削除しました。'
    ]);

    // imageId1は残っていることを確認（紐づけ解除のみ、物理削除は行わない）
    $this->assertDatabaseHas('images', [
        'id' => $imageId1
    ]);

    // imageId2は残っていることを確認（紐づけ解除のみ）
    $this->assertDatabaseHas('images', [
        'id' => $imageId2
    ]);

    // imageId1のrelatedId1の紐づけは解除されていることを確認
    $mapping1Count = DB::table('image_mappings')
        ->where('image_id', $imageId1)
        ->where('related_id', $relatedId1)
        ->count();
    expect($mapping1Count)->toBe(0);

    // imageId2のrelatedId1の紐づけは解除されていることを確認
    $mapping1Count2 = DB::table('image_mappings')
        ->where('image_id', $imageId2)
        ->where('related_id', $relatedId1)
        ->count();
    expect($mapping1Count2)->toBe(0);

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

test('3-1-28: 【一括削除】 未認証ユーザー', function () {
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

test('3-1-29: 【一括削除】 バリデーションエラー（削除 ID 配列バリデーション）', function () {
    $response = $this->actingAs($this->user)->delete('/images/bulk', [
        'ids' => 'not_an_array',
        'related_id' => \Illuminate\Support\Str::uuid()->toString()
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ids']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('idsは配列でなくてはなりません。', $responseData['errors']['ids']);
});

test('3-1-30: 【一括削除】 バリデーションエラー（削除 ID 最小数制限）', function () {
    $response = $this->actingAs($this->user)->delete('/images/bulk', [
        'ids' => [],
        'related_id' => \Illuminate\Support\Str::uuid()->toString()
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ids']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('idsは必ず指定してください。', $responseData['errors']['ids']);
});

test('3-1-31: 【一括削除】 バリデーションエラー（削除 ID UUID 形式バリデーション）', function () {
    $response = $this->actingAs($this->user)->delete('/images/bulk', [
        'ids' => ['invalid-uuid-format'],
        'related_id' => \Illuminate\Support\Str::uuid()->toString()
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['ids.0']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('ids.*に有効なUUIDを指定してください。', $responseData['errors']['ids.0']);
});

test('3-1-32: 【一括削除】 バリデーションエラー（related_id 必須項目）', function () {
    $response = $this->actingAs($this->user)->delete('/images/bulk', [
        'ids' => [\Illuminate\Support\Str::uuid()->toString()]
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['related_id']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('related_idは必ず指定してください。', $responseData['errors']['related_id']);
});

test('3-1-33: 【一括削除】 バリデーションエラー（related_id UUID 形式）', function () {
    $response = $this->actingAs($this->user)->delete('/images/bulk', [
        'ids' => [\Illuminate\Support\Str::uuid()->toString()],
        'related_id' => 'invalid-uuid-format'
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['related_id']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('related_idに有効なUUIDを指定してください。', $responseData['errors']['related_id']);
});

test('3-1-34: 【一括削除】 グループが存在しない', function () {
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

test('3-1-35: 【一括削除】 ImageService 例外（データベースエラー）', function () {
    // 画像をアップロードAPIで作成
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);
    $uploadResponse = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);
    $imageId = $uploadResponse->json('data.0.id');
    $relatedId = \Illuminate\Support\Str::uuid()->toString();

    // ImageServiceのメソッドで例外を発生させる
    $this->mock(ImageService::class, function ($mock) {
        $mock->shouldReceive('deleteImages')
            ->andThrow(new \Exception('Database connection error'));
    });

    $response = $this->actingAs($this->user)->delete('/images/bulk', [
        'ids' => [$imageId],
        'related_id' => $relatedId
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false
    ]);
});

test('3-1-36: 【一括削除】 ImageService 例外', function () {
    // 画像をアップロードAPIで作成
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);
    $uploadResponse = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);
    $imageId = $uploadResponse->json('data.0.id');
    $relatedId = \Illuminate\Support\Str::uuid()->toString();

    // ImageServiceのメソッドで例外を発生させる
    $this->mock(ImageService::class, function ($mock) {
        $mock->shouldReceive('deleteImages')
            ->andThrow(new \Exception('ImageService error'));
    });

    $response = $this->actingAs($this->user)->delete('/images/bulk', [
        'ids' => [$imageId],
        'related_id' => $relatedId
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false
    ]);
});

test('3-1-37: 【一括削除】 ImageService の deleteImages 内で想定外エラー', function () {
    // 画像をアップロードAPIで作成
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);
    $uploadResponse = $this->actingAs($this->user)->post('/images/upload-bulk', [
        'images' => [$file]
    ]);
    $imageId = $uploadResponse->json('data.0.id');
    $relatedId = \Illuminate\Support\Str::uuid()->toString();

    // 本APIは紐づけ解除のみでファイル削除を行わない。ImageService::deleteImages 内で想定外エラーが発生した場合の 500 を検証
    $this->mock(ImageService::class, function ($mock) {
        $mock->shouldReceive('deleteImages')
            ->andThrow(new \Exception('File delete failed'));
    });

    $response = $this->actingAs($this->user)->delete('/images/bulk', [
        'ids' => [$imageId],
        'related_id' => $relatedId
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false
    ]);
});

