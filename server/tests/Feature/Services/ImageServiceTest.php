<?php

use App\Enums\ImageScope;
use App\Models\Group;
use App\Models\Image;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(ImageService::class);
    $this->imageDisk = config('filesystems.image_disk');
    Storage::fake($this->imageDisk);
});

// ===== uploadAndSaveImage() メソッドのテストケース =====

test('4-8-1: 【画像アップロード】 クライアント拡張子に関わらず getimagesize 由来の拡張子で保存する', function () {
    $file = UploadedFile::fake()->image('malicious.php', 100, 100);

    $image = $this->service->uploadAndSaveImage($file, 'groups/test-group-id');

    expect($image->src)->toMatch('/\.jpg$/');
    expect($image->src)->not->toContain('.php');

    $storedFiles = Storage::disk($this->imageDisk)->allFiles('images/groups/test-group-id');
    expect($storedFiles)->toHaveCount(1);
    expect($storedFiles[0])->toMatch('/\.jpg$/');
    expect($storedFiles[0])->not->toContain('.php');
});

test('4-8-2: 【画像アップロード】 正常な JPEG を保存できる', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 120, 80);

    $image = $this->service->uploadAndSaveImage($file, 'users/test-user-id');

    expect($image)->toBeInstanceOf(Image::class);
    expect($image->width)->toBe(120);
    expect($image->height)->toBe(80);
    expect($image->src)->toContain('images/users/test-user-id/');
    expect($image->src)->toMatch('/\.jpg$/');
});

// ===== findImagesByIds() メソッドのテストケース =====

test('4-8-3: 【画像取得】 相対パス src の画像をグループスコープで検証できる', function () {
    $group = Group::create(['group_size' => 1]);
    $image = Image::create([
        'src' => "images/groups/{$group->id}/test.jpg",
        'width' => 100,
        'height' => 100,
    ]);

    $result = $this->service->findImagesByIds([$image->id], $group);

    expect($result)->toHaveCount(1);
    expect($result->first()->id)->toBe($image->id);
});

test('4-8-4: 【画像取得】 相対パス src の画像をユーザースコープで検証できる', function () {
    $user = User::factory()->create();
    $image = Image::create([
        'src' => "images/users/{$user->id}/avatar.jpg",
        'width' => 100,
        'height' => 100,
    ]);

    $result = $this->service->findImagesByIds([$image->id], null, $user, ImageScope::USER);

    expect($result)->toHaveCount(1);
    expect($result->first()->id)->toBe($image->id);
});

test('4-8-5: 【画像取得】 相対パス src が他グループの場合は Not Found', function () {
    $group = Group::create(['group_size' => 1]);
    $otherGroup = Group::create(['group_size' => 1]);
    $image = Image::create([
        'src' => "images/groups/{$otherGroup->id}/test.jpg",
        'width' => 100,
        'height' => 100,
    ]);

    $this->service->findImagesByIds([$image->id], $group);
})->throws(HttpException::class);

// ===== deleteImages() メソッドのテストケース =====

test('4-8-6: 【画像削除】 相対パス src の画像 mapping を解除できる', function () {
    $group = Group::create(['group_size' => 1]);
    $relatedId = (string) Str::uuid();
    $image = Image::create([
        'src' => "images/groups/{$group->id}/recipe.jpg",
        'width' => 100,
        'height' => 100,
    ]);
    DB::table('image_mappings')->insert([
        'image_id' => $image->id,
        'group_id' => $group->id,
        'related_model' => 'Recipe',
        'related_id' => $relatedId,
        'image_type' => 'thumbnail',
        'order' => 0,
    ]);

    $count = $this->service->deleteImages([$image->id], $relatedId, $group);

    expect($count)->toBe(1);
    $this->assertDatabaseMissing('image_mappings', [
        'image_id' => $image->id,
        'related_id' => $relatedId,
        'group_id' => $group->id,
    ]);
    $this->assertDatabaseHas('images', ['id' => $image->id]);
});

test('4-8-7: 【画像削除】 相対パス src が他グループの場合は mapping を解除しない', function () {
    $group = Group::create(['group_size' => 1]);
    $otherGroup = Group::create(['group_size' => 1]);
    $relatedId = (string) Str::uuid();
    $image = Image::create([
        'src' => "images/groups/{$otherGroup->id}/recipe.jpg",
        'width' => 100,
        'height' => 100,
    ]);
    DB::table('image_mappings')->insert([
        'image_id' => $image->id,
        'group_id' => $group->id,
        'related_model' => 'Recipe',
        'related_id' => $relatedId,
        'image_type' => 'thumbnail',
        'order' => 0,
    ]);

    $count = $this->service->deleteImages([$image->id], $relatedId, $group);

    expect($count)->toBe(0);
    $this->assertDatabaseHas('image_mappings', [
        'image_id' => $image->id,
        'related_id' => $relatedId,
        'group_id' => $group->id,
    ]);
});

// ===== deleteImagesByGroup() / deleteImagesByUser() メソッドのテストケース =====

test('4-8-8: 【グループ画像一括削除】 相対パス src の images レコードとディレクトリを削除する', function () {
    $group = Group::create(['group_size' => 1]);
    $dirPath = "images/groups/{$group->id}";
    $filePath = "{$dirPath}/test.jpg";
    Storage::disk($this->imageDisk)->put($filePath, 'fake');
    $image = Image::create([
        'src' => $filePath,
        'width' => 100,
        'height' => 100,
    ]);

    $this->service->deleteImagesByGroup($group);

    $this->assertDatabaseMissing('images', ['id' => $image->id]);
    expect(Storage::disk($this->imageDisk)->exists($dirPath))->toBeFalse();
});

test('4-8-9: 【ユーザー画像一括削除】 相対パス src の images レコードとディレクトリを削除する', function () {
    $user = User::factory()->create();
    $dirPath = "images/users/{$user->id}";
    $filePath = "{$dirPath}/avatar.jpg";
    Storage::disk($this->imageDisk)->put($filePath, 'fake');
    $image = Image::create([
        'src' => $filePath,
        'width' => 100,
        'height' => 100,
    ]);

    $this->service->deleteImagesByUser($user);

    $this->assertDatabaseMissing('images', ['id' => $image->id]);
    expect(Storage::disk($this->imageDisk)->exists($dirPath))->toBeFalse();
});

// ===== formatImage() / generateImageUrl() メソッドのテストケース =====

test('4-8-10: 【画像URL生成】 public ディスクでは公開 URL を返す', function () {
    $path = 'images/groups/test-group/test.jpg';
    Storage::disk($this->imageDisk)->put($path, 'fake');
    $image = Image::create([
        'src' => $path,
        'width' => 100,
        'height' => 100,
    ]);

    $formatted = $this->service->formatImage($image);

    expect($formatted['src'])->toBe(Storage::disk($this->imageDisk)->url($path));
});

test('4-8-11: 【画像URL生成】 s3 ディスクでは署名付き URL を返す', function () {
    Storage::fake('s3');
    config(['filesystems.image_disk' => 's3']);

    $path = 'images/groups/test-group/test.jpg';
    Storage::disk('s3')->put($path, 'fake');
    $image = Image::create([
        'src' => $path,
        'width' => 100,
        'height' => 100,
    ]);

    $service = app(ImageService::class);
    $formatted = $service->formatImage($image);

    expect($formatted['src'])->not->toBe($path);
    expect($formatted['src'])->toMatch('/\?expiration=\d+$/');
});
