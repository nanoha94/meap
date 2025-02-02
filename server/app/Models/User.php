<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\Api\Auth\Custom\CustomResetPasswordNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'custom_id',
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
     * Send a password reset notification to the user.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomResetPasswordNotification($token));
    }

    protected $keyType = 'string'; // UUIDの場合はstring
    public $incrementing = false; // UUIDは自動インクリメントしないため

    /**
     * ユニークなカスタムIDを生成
     * TODO: 無限ループの可能性あり
     */
    private static function generateUniqueCustomId(): string
    {
        // 使用する文字セット（見間違いやすい文字を除外）
        $characters = 'abcdefghjkmnpqrstuvwxyz23456789';

        do {
            $customId = '';
            // 8文字のランダムな文字列を生成
            for ($i = 0; $i < 8; $i++) {
                $customId .= $characters[random_int(0, strlen($characters) - 1)];
            }
            // 生成したIDが既に存在するかチェック
        } while (static::where('custom_id', $customId)->exists());

        return $customId;
    }

    // モデルのイベントを使用してcustom_idを設定
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // custom_idを生成して設定
            $user->custom_id = static::generateUniqueCustomId();
        });
    }

    public function groupUser()
    {
        return $this->hasOne(GroupUser::class);
    }

    public function invitationTokens()
    {
        return $this->hasMany(InvitationToken::class, 'inviter_id');
    }
}
