<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingHistory extends Model
{
    protected $fillable = [
        'group_id',
        'square_payment_id',
        'type',
        'description',
        'amount',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_at' => 'datetime',
    ];

    /**
     * 所属グループを取得する
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
