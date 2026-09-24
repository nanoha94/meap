<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'group_id',
        'payjp_subscription_id',
        'payjp_plan_id',
        'status',
        'trial_ends_at',
        'ends_at',
        'current_period_start',
        'current_period_end',
        'canceled_at',
        'paused_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'canceled_at' => 'datetime',
        'paused_at' => 'datetime',
    ];

    /**
     * 所属グループを取得する
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * PAY.JP 上で契約が継続中かどうか（解約・停止前）。
     *
     * meap ではトライアル期間は提供しないが、PAY.JP の status や将来のプラン設定に備え trial も active と同様に扱う。
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial'], true);
    }

    /**
     * 解約済みだが契約期間終了まで利用可能な猶予期間かどうか
     */
    public function onGracePeriod(): bool
    {
        return $this->status === 'canceled'
            && $this->ends_at !== null
            && $this->ends_at->isFuture();
    }

    /**
     * サブスクリプション特典が有効かどうか（猶予期間を含む）
     */
    public function grantsSubscriptionAccess(): bool
    {
        return $this->isActive() || $this->onGracePeriod();
    }
}
