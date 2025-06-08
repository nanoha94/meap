<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupUserMapping extends Model
{
    protected $table = 'group_user_mappings';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'group_id',
    ];
}
