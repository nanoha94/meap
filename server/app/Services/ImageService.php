<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Enums\ImageScope;
use App\Exceptions\SafeUrlFetchException;
use App\Helpers\SafeUrlFetcher;
use App\Models\Group;
use App\Models\Image;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LoggingTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ImageManagerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ImageService
{
    use LoggingTrait;

    private const MAX_IMAGE_BYTES = 10 * 1024 * 1024;
    private const MAX_LONG_SIDE_PX = 2000;

    /**
     * 画像をアップロードして保存
     *
     * ストレージ失敗・DB 失敗時は例外（上位でハンドリング想定）
     *
     * {@see downloadAndSaveImage} との挙動の違い: こちらは API 上のファイルアップロード用。失敗時は例外が
     * そのまま伝播し、戻り値は常に {@see Image}。リモート URL 取り込み用の {@see downloadAndSaveImage} は
     * 失敗を null 返却＋警告ログに抑え、例外は投げない（ベストエフォート）。
     *
     * @return \App\Models\Image
     */
    public function uploadAndSaveImage(UploadedFile $file, string $uploadPath): Image
    {
        // 拡張子はクライアント名ではなく getimagesize の IMAGETYPE_* から決定（{@see downloadAndSaveImage} と同様）
        // @ は不正バイナリで PHP が出す E_WARNING を抑え、失敗通知は下記 HttpException に統一するため付与
        $imageInfo = @getimagesize($file->getPathname());
        if ($imageInfo === false) {
            throw new HttpException(
                HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                __('api.general.server_error')
            );
        }

        $imageType = $imageInfo[2] ?? 0;
        $extension = $this->imageTypeToExtension($imageType);
        if ($extension === null) {
            throw new HttpException(
                HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                __('api.general.server_error')
            );
        }

        $mediaType = $this->imageTypeToMediaType($imageType);
        if ($mediaType === null) {
            throw new HttpException(
                HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                __('api.general.server_error')
            );
        }

        $fileName = $this->generateFileName($extension);
        $fullPath = "images/{$uploadPath}/{$fileName}";

        try {
            $image = $this->imageManager()->decodePath($file->getPathname());
            $processed = $this->stripResizeEncodeRaster($image, $mediaType);
        } catch (Throwable $e) {
            throw new HttpException(
                HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                __('api.general.server_error'),
                $e
            );
        }

        $this->imageDisk()->put($fullPath, $processed['binary']);

        return DB::transaction(function () use ($fullPath, $processed) {
            return Image::create([
                'src' => $fullPath,
                'width' => $processed['width'],
                'height' => $processed['height'],
            ]);
        });
    }

    /**
     * リモート URL から画像を取得し、storage（public ディスク）に保存する。
     *
     * 用途例: OAuth プロバイダ（Google 等）が返すアバター画像 の取り込み。
     * 拡張子・形式は本文を {@see getimagesizefromstring} で解釈し、
     * {@see uploadAndSaveImage} の mimes（jpeg, png, gif, webp）に合致するもののみ保存する。
     * 各種失敗時は null を返し、警告ログのみ。例外は再送出しない（ログイン等の上位フローを止めない）。
     *
     * {@see uploadAndSaveImage} との挙動の違い: こちらはリモート取得用で失敗を握り null を返すだけ。
     * アップロード API 用の {@see uploadAndSaveImage} はストレージ／DB 失敗時に例外を投げ、失敗を HTTP エラー
     * 等で表現する。上位処理を止めないため。
     *
     * @param string $url 取得元の HTTP(S) URL
     * @param string $uploadPath storage 上の相対パス接頭辞（例: users/{id}。先頭の images/ は付けない）
     * @return \App\Models\Image|null 成功時は作成した Image、いずれかの段階で失敗したら null
     */
    public function downloadAndSaveImage(string $url, string $uploadPath): ?Image
    {
        $logFail = function (string $reason, array $extra = []) use ($url): void {
            $this->logWarning(
                __('operations.image.download_remote'),
                __('api.image.remote_download_failed'),
                ['url' => $url, 'reason' => $reason] + $extra,
                __METHOD__,
            );
        };

        // 空文字・非 URL は早期終了
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            $logFail('invalid_url');
            return null;
        }

        // OAuth アバター取得は許可済み CDN のみ（SSRF 対策）
        if (! $this->isAllowedRemoteAvatarHost($url)) {
            $logFail('host_not_allowed');
            return null;
        }

        // Google CDN（lh3-7.googleusercontent.com など）の URL はデフォルトで 96px のサムネイルが返るため、
        // 末尾の =sNN[-c] サイズ指定を高解像度（=s512-c）に置換してから取得する。
        $url = $this->normalizeRemoteImageUrl($url);

        // SSRF 対策付きで取得（HTTPS 限定・内部 IP 拒否・リダイレクト無効）
        try {
            $body = SafeUrlFetcher::fetch($url, maxBytes: self::MAX_IMAGE_BYTES);
        } catch (SafeUrlFetchException $e) {
            $this->logWarning(
                __('operations.image.download_remote'),
                __('api.image.remote_download_failed'),
                $e->toLogContext($url),
                __METHOD__,
            );

            return null;
        }

        // 解釈可能なラスタ画像か検証。拡張子は imageInfo[2]（IMAGETYPE_*）から {@see imageTypeToExtension} で決定
        // @ は不正バイナリで PHP が出す E_WARNING を抑え、失敗通知は下記 logWarning に統一するため付与
        $imageInfo = @getimagesizefromstring($body);
        if ($imageInfo === false) {
            $logFail('not_a_valid_image');
            return null;
        }

        $imageType = $imageInfo[2] ?? 0;
        $extension = $this->imageTypeToExtension($imageType);
        if ($extension === null) {
            $logFail('unsupported_image_type', ['image_type' => $imageType]);
            return null;
        }

        $mediaType = $this->imageTypeToMediaType($imageType);

        $fileName = $this->generateFileName($extension);
        $fullPath = "images/{$uploadPath}/{$fileName}";

        try {
            $image = $this->imageManager()->decodeBinary($body);
            $processed = $this->stripResizeEncodeRaster($image, $mediaType);
        } catch (Throwable $e) {
            $logFail('image_process_failed', ['exception_message' => $e->getMessage()]);

            return null;
        }

        try {
            $this->imageDisk()->put($fullPath, $processed['binary']);
        } catch (Throwable $e) {
            $logFail('storage_put_failed', ['exception_message' => $e->getMessage()]);

            return null;
        }

        try {
            return DB::transaction(function () use ($fullPath, $processed) {
                return Image::create([
                    'src' => $fullPath,
                    'width' => $processed['width'],
                    'height' => $processed['height'],
                ]);
            });
        } catch (Throwable $e) {
            try {
                if ($this->imageDisk()->exists($fullPath)) {
                    $this->imageDisk()->delete($fullPath);
                }
            } catch (Throwable) {
                // 掃除失敗は握り潰し（上位でログ済みの文脈に追加しない）
            }
            $logFail('db_transaction_failed', ['exception_message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * 指定されたIDの画像を取得し、スコープで検証
     * 
     * @param array $imageIds 取得する画像IDの配列
     * @param \App\Models\Group|null $group グループスコープ検証時に使用するグループ（scope=ImageScope::GROUPの場合に必須）
     * @param \App\Models\User|null $user ユーザースコープ検証時に使用するユーザー（scope=ImageScope::USERの場合に使用）
     * @param \App\Enums\ImageScope $scope 検証スコープ。デフォルトは ImageScope::GROUP
     * @return \Illuminate\Support\Collection 検証済みの画像コレクション
     * @throws HttpException 画像が見つからない、または指定されたスコープに属していない場合
     */
    public function findImagesByIds(
        array $imageIds,
        ?Group $group = null,
        ?User $user = null,
        ImageScope $scope = ImageScope::GROUP
    ): Collection {
        if (empty($imageIds)) {
            return collect();
        }

        $images = Image::whereIn('id', $imageIds)->get();

        // スコープに応じてパターンを決定
        $pattern = $this->getScopePattern($scope, $group, $user);

        // すべての画像が存在し、かつ指定されたスコープに属していることを確認
        $validImages = collect();
        foreach ($imageIds as $imageId) {
            $image = $images->firstWhere('id', $imageId);

            if (!$image) {
                throw new HttpException(
                    HttpStatusCode::NOT_FOUND->value,
                    __('api.not_found', ['attribute' => __('api.attributes.image')])
                );
            }

            if (!preg_match($pattern, $image->src)) {
                throw new HttpException(
                    HttpStatusCode::NOT_FOUND->value,
                    __('api.not_found', ['attribute' => __('api.attributes.image')])
                );
            }

            $validImages->push($image);
        }

        return $validImages;
    }

    /**
     * 画像を一括削除
     * 
     * 指定されたrelatedIdとの紐づけを解除します。
     * imagesテーブルからは削除せず、紐づけ解除のみを行います。
     * 
     * 呼び出し元が認可済みの related_id を渡すこと。本メソッドは mapping 解除と画像 src のグループ一致のみ行う。
     *
     * @param array $imageIds 削除する画像IDの配列
     * @param string $relatedId 紐づけを解除するエンティティのID（必須）
     * @param \App\Models\Group $group 画像 src のグループ一致検証および mapping 削除用
     * @return int 紐づけ解除された画像の数
     */
    public function deleteImages(array $imageIds, string $relatedId, Group $group): int
    {
        if (empty($imageIds)) {
            return 0;
        }

        // 指定されたIDの画像を取得し、グループIDがパスに含まれているかチェック
        $images = Image::whereIn('id', $imageIds)->get();
        $escapedGroupId = preg_quote($group->id, '/');
        $groupIdPattern = "/images\\/groups\\/{$escapedGroupId}\\//";

        // 削除対象の画像を抽出（グループチェック）
        $imagesToProcess = [];
        foreach ($images as $image) {
            if (!preg_match($groupIdPattern, $image->src)) {
                $this->logWarning(__('operations.image.bulk_destroy'), __('api.image.group_mismatch'), [
                    'image_id' => $image->id,
                    'image_src' => $image->src,
                    'expected_group_id' => $group->id
                ], __METHOD__);
                continue;
            }
            $imagesToProcess[] = $image;
        }

        if (empty($imagesToProcess)) {
            return 0;
        }

        // トランザクション内で紐づけ解除を実行
        $unlinkedCount = DB::transaction(function () use ($imagesToProcess, $relatedId, $group) {
            $count = 0;
            foreach ($imagesToProcess as $image) {
                // 指定された紐づけを解除（主キー: image_id, related_id, group_id）
                $deletedMappingCount = DB::table('image_mappings')
                    ->where('image_id', $image->id)
                    ->where('group_id', $group->id)
                    ->where('related_id', $relatedId)
                    ->delete();

                if ($deletedMappingCount > 0) {
                    $count++;
                }
            }
            return $count;
        });

        return $unlinkedCount;
    }

    /**
     * グループ配下の画像を一括削除（ディレクトリ削除でファイルもまとめて削除＋images レコード削除）
     */
    public function deleteImagesByGroup(Group $group): void
    {
        $this->purgeImagesUnder(
            'images/groups/' . $group->id,
            __('operations.image.delete_images_by_group')
        );
    }

    /**
     * ユーザー配下の画像を一括削除（ディレクトリ削除でファイルもまとめて削除＋images レコード削除）
     */
    public function deleteImagesByUser(User $user): void
    {
        $this->purgeImagesUnder(
            'images/users/' . $user->id,
            __('operations.image.delete_images_by_user')
        );
    }

    /**
     * 画像情報をフォーマット
     */
    public function formatImage(?Image $image): ?array
    {
        if (!$image) {
            return null;
        }

        return [
            'id' => $image->id,
            'src' => $this->generateImageUrl($image->src),
            'width' => $image->width,
            'height' => $image->height,
        ];
    }

    /**
     * DB に保存された相対パスからクライアント向け URL を生成する。
     */
    private function generateImageUrl(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $disk = $this->imageDisk();
        $diskName = config('filesystems.image_disk');
        $driver = config("filesystems.disks.{$diskName}.driver");

        // s3（R2）ディスク時は署名付き URL、public ディスク時は従来の公開 URL を返す
        if ($driver === 's3') {
            $ttl = (int) config('filesystems.signed_url_ttl', 360);

            return $disk->temporaryUrl($path, now()->addMinutes($ttl));
        }

        // 従来の公開 URL を返す
        return $disk->url($path);
    }

    /**
     * 画像の一括アップロードレスポンスをフォーマット
     */
    public function formatBulkImageUploadResponse(Collection $images): array
    {
        return collect($images)
            ->map(fn($image) => $this->formatImage($image))
            ->toArray();
    }


    /**
     * 画像ファイルの配列を取得（無効なファイルを除外）
     */
    public function getValidImageFiles(Request $request, $maxImages = 20): array
    {
        return collect(range(0, $maxImages - 1))
            ->map(fn($i) => $request->file("images.{$i}"))
            ->filter(fn($file) => $file && $file->isValid())
            ->values()
            ->toArray();
    }

    /**
     * 指定ディレクトリ配下の Image 行を削除し、storage 上のディレクトリを削除
     *
     * @param string $relativeDir storage public 基準。例: images/groups/{id}, images/users/{id}
     * @param string $operation logWarning 用の操作名（翻訳済み）
     */
    private function purgeImagesUnder(string $relativeDir, string $operation): void
    {
        Image::where('src', 'like', '%' . $relativeDir . '/%')->delete();
        if (!$this->deleteImageDirectory($relativeDir)) {
            $this->logWarning($operation, __('api.image.file_delete_failed'), [
                'directory' => $relativeDir,
            ], __METHOD__);
        }
    }

    /**
     * スコープに応じたパスパターンを取得
     *
     * @param \App\Enums\ImageScope $scope 検証スコープ
     * @param \App\Models\Group|null $group グループ（groupスコープ時に使用）
     * @param \App\Models\User|null $user ユーザー（userスコープ時に使用）
     * @return string 正規表現パターン
     * @throws HttpException 必須パラメータが不足している場合
     */
    private function getScopePattern(ImageScope $scope, ?Group $group, ?User $user): string
    {
        return match ($scope) {
            ImageScope::USER => $this->buildScopePattern('users', $user),
            ImageScope::GROUP => $this->buildScopePattern('groups', $group),
        };
    }

    /**
     * スコープパス（images/{segment}/{owner_id}/）にマッチする正規表現を組み立てる
     *
     * @param string $segment パス上の区間（`users` または `groups`）
     * @param  Model|null  $owner User または Group
     * @return string 正規表現パターン
     * @throws HttpException オーナーが null の場合
     */
    private function buildScopePattern(string $segment, ?Model $owner): string
    {
        if ($owner === null) {
            [$entity, $scopeWord] = match ($segment) {
                'users' => ['User', 'user'],
                'groups' => ['Group', 'group'],
            };
            throw new HttpException(
                HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                "{$entity} is required for {$scopeWord} scope validation"
            );
        }
        $escapedId = preg_quote($owner->id, '/');

        return "/images\\/{$segment}\\/{$escapedId}\\//";
    }

    /**
     * 衝突しにくいファイル名を生成（{timestamp}_{uniqid}.{ext}）
     *
     * @param string $extension 拡張子（先頭の . は有っても可）
     */
    private function generateFileName(string $extension): string
    {
        $safe = ltrim($extension, '.');
        $timestamp = now()->timestamp;
        $random = uniqid();

        return "{$timestamp}_{$random}.{$safe}";
    }

    /**
     * ImageManager インスタンスを取得
     *
     * @return ImageManagerInterface
     */
    private function imageManager(): ImageManagerInterface
    {
        return ImageManager::usingDriver(GdDriver::class);
    }

    private function imageDisk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk(config('filesystems.image_disk'));
    }

    /**
     * 再エンコードで Exif 等を除去し、長辺を {@see MAX_LONG_SIDE_PX} 以下に縮小する（拡大しない）。
     *
     * @return array{binary: string, width: int, height: int}
     */
    private function stripResizeEncodeRaster(ImageInterface $image, string $mediaType): array
    {
        $processed = $image->scaleDown(width: self::MAX_LONG_SIDE_PX, height: self::MAX_LONG_SIDE_PX);
        $encoded = $processed->encodeUsingMediaType($mediaType);

        return [
            'binary' => $encoded->toString(),
            'width' => $processed->width(),
            'height' => $processed->height(),
        ];
    }

    /**
     * getimagesize の IMAGETYPE_* から HTTP メディアタイプへ
     */
    private function imageTypeToMediaType(int $imageType): ?string
    {
        return match ($imageType) {
            IMAGETYPE_JPEG => 'image/jpeg',
            IMAGETYPE_PNG => 'image/png',
            IMAGETYPE_GIF => 'image/gif',
            IMAGETYPE_WEBP => 'image/webp',
            default => null,
        };
    }

    /**
     * OAuth アバター取得で許可するリモートホストかどうか。
     *
     * 現状は Google OAuth（*.googleusercontent.com）のみ対応。
     */
    private function isAllowedRemoteAvatarHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host)) {
            return false;
        }

        return (bool) preg_match('/(^|\.)googleusercontent\.com$/i', $host);
    }

    /**
     * リモート画像 URL を高解像度向けに正規化する。
     *
     * 現状は Google ユーザーコンテンツ CDN（*.googleusercontent.com）のみ対象。
     * Google CDN は URL 末尾の =sNN[-c] でサイズが固定されており、Socialite の getAvatar() は
     * デフォルト 96px のサムネイル URL を返すため、そのまま保存するとアバター表示が荒くなる。
     * 末尾の =sNN[-c] を =s512-c に置換することで、512px の画像を取得して保存する。
     *
     * 対象外のホストや、サイズ指定が無い URL はそのまま返す。
     */
    private function normalizeRemoteImageUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || ! preg_match('/(^|\.)googleusercontent\.com$/', $host)) {
            return $url;
        }

        return preg_replace('/=s\d+(-c)?$/', '=s512-c', $url) ?? $url;
    }

    /**
     * getimagesize / getimagesizefromstring の IMAGETYPE_* から、保存用拡張子を返す
     * （mimes: jpeg,png,gif,webp に合わせる）
     */
    private function imageTypeToExtension(int $imageType): ?string
    {
        return match ($imageType) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_WEBP => 'webp',
            default => null,
        };
    }

    /**
     * 画像用ディレクトリを削除（storage public ディスク基準の相対パス）
     *
     * @param string $relativePath 例: images/groups/{group_id} または images/users/{user_id}
     */
    private function deleteImageDirectory(string $relativePath): bool
    {
        try {
            if ($this->imageDisk()->exists($relativePath)) {
                $this->imageDisk()->deleteDirectory($relativePath);
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
