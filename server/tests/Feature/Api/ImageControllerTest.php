<?php

use App\Models\User;
use App\Models\Group;
use App\Models\Image;
use App\Models\Color;
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

    // ImageService が参照するディスクをフェイク（phpunit.xml で IMAGE_DISK=public を固定）
    $this->imageDisk = config('filesystems.image_disk');
    Storage::fake($this->imageDisk);

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

// ===== bulkUploadForGroup() メソッドのテストケース =====

test('3-1-1: 【一括アップロード】 正常な画像アップロード（1 枚）', function () {
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->actingAs($this->user)->post('/images/groups/upload-bulk', [
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
    expect($image->src)->toContain("images/groups/{$this->group->id}/");
    expect($data[0]['src'])->toBe(Storage::disk($this->imageDisk)->url($image->src));
});

test('3-1-2: 【一括アップロード】 複数画像の一括アップロード', function () {
    $files = [
        UploadedFile::fake()->image('test1.jpg', 100, 100),
        UploadedFile::fake()->image('test2.jpg', 100, 100),
        UploadedFile::fake()->image('test3.jpg', 100, 100)
    ];

    $response = $this->actingAs($this->user)->post('/images/groups/upload-bulk', [
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

    $response = $this->actingAs($this->user)->post('/images/groups/upload-bulk', [
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
    expect($image->src)->toContain("images/groups/{$this->group->id}/");
    // ディレクトリ名が含まれていないことを確認
    expect($image->src)->not->toContain("images/groups/{$this->group->id}/general/");
    expect($image->src)->not->toContain("images/groups/{$this->group->id}/recipes/");
});

test('3-1-4: 【一括アップロード】 upload_path を送っても無視され groups 配下に保存される', function () {
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->actingAs($this->user)->post('/images/groups/upload-bulk', [
        'images' => [$file],
        'upload_path' => 'users',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '画像を1件アップロードしました。',
    ]);

    $image = Image::where('width', 100)->where('height', 100)->first();
    expect($image->src)->toContain("images/groups/{$this->group->id}/");
    expect($image->src)->not->toContain('images/users/');
});

test('3-1-5: 【一括アップロード】 upload_path 未指定時にグループ ID 配下に保存される', function () {
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->actingAs($this->user)->post('/images/groups/upload-bulk', [
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
    expect($image->src)->toContain("images/groups/{$this->group->id}/");
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

    $response = $this->actingAs($this->user)->post('/images/groups/upload-bulk', [
        'images' => [$file]
    ]);

    $response->assertStatus(200);

    $dir = "images/groups/{$this->group->id}";
    $stored = Storage::disk($this->imageDisk)->files($dir);
    expect($stored)->toHaveCount(1);

    $bin = Storage::disk($this->imageDisk)->get($stored[0]);
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

    $response = $this->actingAs($this->user)->post('/images/groups/upload-bulk', [
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

    $response = $this->actingAs($this->user)->post('/images/groups/upload-bulk', [
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

        $response = $this->actingAs($this->user)->post('/images/groups/upload-bulk', [
            'images' => [$file]
        ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data[0]['width'])->toBe(2000);
        expect($data[0]['height'])->toBe(1000);

        $dir = "images/groups/{$this->group->id}";
        $paths = Storage::disk($this->imageDisk)->files($dir);
        $path = collect($paths)->first(fn ($p) => str_ends_with($p, '.' . $format['ext']));
        expect($path)->not->toBeNull();

        $bin = Storage::disk($this->imageDisk)->get($path);
        $info = @getimagesizefromstring($bin);
        expect($info)->not->toBeFalse();
        expect($info[2])->toBe($format['type']);
    }
});

test('3-1-10: 【一括アップロード】 未認証ユーザー', function () {
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->post('/images/groups/upload-bulk', [
        'images' => [$file]
    ]);

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。'
    ]);
});

test('3-1-11: 【一括アップロード】 バリデーションエラー（ファイル配列バリデーション）', function () {
    $response = $this->actingAs($this->user)->post('/images/groups/upload-bulk', [
        'images' => 'not_an_array'
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['images']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('imagesは配列でなくてはなりません。', $responseData['errors']['images']);
});

test('3-1-12: 【一括アップロード】 バリデーションエラー（最小ファイル数制限）', function () {
    $response = $this->actingAs($this->user)->post('/images/groups/upload-bulk', [
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

    $response = $this->actingAs($this->user)->post('/images/groups/upload-bulk', [
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

    $response = $this->actingAs($this->user)->post('/images/groups/upload-bulk', [
        'images' => [$file]
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['images.0']);

    // エラーメッセージの確認
    $responseData = $response->json();
    $this->assertContains('images.*には、10240 kb以下のファイルを指定してください。', $responseData['errors']['images.0']);
});

test('3-1-17: 【一括アップロード】 グループが存在しない', function () {
    $user = User::factory()->create([
        'email_verified_at' => now()
    ]);
    // グループに所属させない

    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->actingAs($user)->post('/images/groups/upload-bulk', [
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

    $response = $this->actingAs($this->user)->post('/images/groups/upload-bulk', [
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

    $response = $this->actingAs($this->user)->post('/images/groups/upload-bulk', [
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
        ->with(config('filesystems.image_disk'))
        ->andReturnSelf();
    Storage::shouldReceive('put')
        ->andThrow(new \Exception('File upload failed'));

    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->actingAs($this->user)->post('/images/groups/upload-bulk', [
        'images' => [$file]
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false
    ]);
});

// ===== uploadForUser() メソッドのテストケース =====

test('3-1-21: 【アップロード】 正常な画像アップロード', function () {
    $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

    $response = $this->actingAs($this->user)->post('/images/users/upload', [
        'image' => $file,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '画像をアップロードしました。',
    ]);

    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'src',
            'width',
            'height',
        ],
    ]);

    $data = $response->json('data');
    expect($data)->toHaveKeys(['id', 'src', 'width', 'height']);
    expect($data['width'])->toBe(100);
    expect($data['height'])->toBe(100);

    $this->assertDatabaseHas('images', [
        'width' => 100,
        'height' => 100,
    ]);

    $image = Image::where('width', 100)->where('height', 100)->first();
    expect($image->src)->toContain("images/users/{$this->user->id}/");
    expect($data['src'])->toBe(Storage::disk($this->imageDisk)->url($image->src));
});

test('3-1-22: 【アップロード】 メール未確認ユーザー', function () {
    $unverifiedUser = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

    $response = $this->actingAs($unverifiedUser)->post('/images/users/upload', [
        'image' => $file,
    ]);

    $response->assertStatus(409);
    $response->assertJson([
        'success' => false,
        'message' => 'Your email address is not verified.',
    ]);
    $response->assertJsonStructure([
        'success',
        'message',
    ]);
    $response->assertHeader('Content-Type', 'application/json');
});

test('3-1-23: 【アップロード】 未認証ユーザー', function () {
    $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

    $response = $this->post('/images/users/upload', [
        'image' => $file,
    ]);

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => '認証が必要です。',
    ]);
});

test('3-1-24: 【アップロード】 バリデーションエラー（image 必須）', function () {
    $response = $this->actingAs($this->user)->post('/images/users/upload', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['image']);

    $responseData = $response->json();
    $this->assertContains('imageは必ず指定してください。', $responseData['errors']['image']);
});

test('3-1-25: 【アップロード】 バリデーションエラー（ファイル形式不正）', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100);

    $response = $this->actingAs($this->user)->post('/images/users/upload', [
        'image' => $file,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['image']);
});

