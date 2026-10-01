<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'group_id',
        'square_subscription_id',
        'square_plan_variation_id',
        'status',
        'ends_at',
        'current_period_start',
        'current_period_end',
        'canceled_at',
    ];

    protected $casts = [
        'ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    /**
     * 所属グループを取得する
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * 契約が継続中かどうか（解約・停止前）。
     *
     * meap ではトライアル期間は提供しないが、status が trial の場合も active と同様に扱う。
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
