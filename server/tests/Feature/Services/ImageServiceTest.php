<?php

use App\Models\Image;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
