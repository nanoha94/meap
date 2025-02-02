<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Exception;
use Illuminate\Support\Facades\Hash;

class InvitationToken extends Model
{
    use  HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'inviter_id',
        'token',
        'expires_at',
    ];

    protected $casts = [
        'token' => 'hashed',
        'expires_at' => 'datetime',
    ];

    public static function generateToken(): string
    {
        return Str::random(32);
    }

    public static function createWithExpiration($inviterId,  $expiresAt): string
    {
        $maxAttempts = 5; // 最大試行回数
        $attempt = 0;

        do {
            $attempt++;

            $token = self::generateToken();

            if ($attempt >= $maxAttempts) {
                throw new Exception('トークン生成に失敗しました。');
            }
        } while (InvitationToken::where('token', $token)->exists());

        self::create([
            'inviter_id' => $inviterId,
            'token' => Hash::make($token),
            'expires_at' => $expiresAt,
        ]);

        return $token;
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }
}
