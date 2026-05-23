<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/health', function () {
    $timings = [];
    $start = microtime(true);

    // 1) Laravel bootstrap (この時点で完了済み)
    $timings['framework_boot_ms'] = round((microtime(true) - $start) * 1000, 1);

    // 2) DB connection + simple query
    $dbStart = microtime(true);
    DB::select('SELECT 1');
    $timings['db_select1_ms'] = round((microtime(true) - $dbStart) * 1000, 1);

    // 3) Session table read (database driver overhead)
    $sessStart = microtime(true);
    DB::table('sessions')->where('id', 'none')->first();
    $timings['session_table_query_ms'] = round((microtime(true) - $sessStart) * 1000, 1);

    // 4) User table query
    $userStart = microtime(true);
    User::first();
    $timings['user_query_ms'] = round((microtime(true) - $userStart) * 1000, 1);

    $timings['total_ms'] = round((microtime(true) - $start) * 1000, 1);

    return response()->json($timings);
});



require __DIR__ . '/auth.php';
