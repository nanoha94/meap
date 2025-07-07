<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\Auth\CustomResetPasswordNotification;
use App\Notifications\Auth\CustomVerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
    private static function generateUniqueCustomId(): string
    {
        // 使用する文字セット（見間違いやすい文字を除外）
        $characters = 'abcdefghjkmnpqrstuvwxyz23456789';

        $customId = '';
        // 8文字のランダムな文字列を生成
        for ($i = 0; $i < 8; $i++) {
            $customId .= $characters[random_int(0, strlen($characters) - 1)];
        }
        // 生成したIDが既に存在するかチェック

        return $customId;
    }

    public function groupUser(): HasOne
    {
        return $this->hasOne(GroupUserMapping::class);
    }

    public function group()
    {
        return $this->hasOneThrough(Group::class, GroupUserMapping::class, 'user_id', 'id', 'id', 'group_id');
    }

    public function invitationTokens()
    {
        return $this->hasMany(InvitationToken::class, 'inviter_id');
    }
}
