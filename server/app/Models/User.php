<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\Auth\CustomResetPasswordNotification;
use App\Notifications\Auth\CustomVerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'language',
        'avatar_seed', // アイコン生成用のシード値
        'avatar_image_url',
        'avatar_image_width',
        'avatar_image_height',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'language' => 'ja',
    ];

    /**
     * Send a password reset notification to the user.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomResetPasswordNotification($token));
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new CustomVerifyEmailNotification());
    }

    protected $keyType = 'string'; // UUIDの場合はstring
    public $incrementing = false; // UUIDは自動インクリメントしないため

    /**
     * ユニークなカスタムIDを生成
     */
    public static function generateUniqueCustomId(): string
    {
        // 使用する文字セット（見間違いやすい文字を除外）
        $characters = 'abcdefghjkmnpqrstuvwxyz23456789';
        $maxAttempts = 100; // 最大試行回数
        $attempt = 0;

        do {
            $customId = '';
            // 8文字のランダムな文字列を生成
            for ($i = 0; $i < 8; $i++) {
                $customId .= $characters[random_int(0, strlen($characters) - 1)];
            }

            $attempt++;

            // 最大試行回数を超えた場合は例外を投げる
            if ($attempt >= $maxAttempts) {
                throw new \Exception("ユニークなIDの生成に失敗しました。もう一度お試しください。");
            }

            // 生成したIDが既に存在するかチェック
        } while (self::where('avatar_seed', $customId)->exists());

        return $customId;
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_user_mappings', 'user_id', 'group_id');
    }


    public function invitationTokens()
    {
        return $this->hasMany(InvitationToken::class, 'inviter_user_id');
    }
}
