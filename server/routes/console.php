<?php

use App\Models\InvitationToken;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    InvitationToken::where('expires_at', '<', now())->delete();
})->daily()->description('Delete expired invitation tokens');
