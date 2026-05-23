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

    $dbHost = config('database.connections.pgsql.host') ?? 'unknown';
    $timings['db_host'] = $dbHost;

    // 1) DNS resolution
    $dnsStart = microtime(true);
    $resolved = gethostbyname($dbHost);
    $timings['dns_ms'] = round((microtime(true) - $dnsStart) * 1000, 1);
    $timings['resolved_ip'] = $resolved;

    // 2) DB connection + SELECT 1
    $dbStart = microtime(true);
    DB::select('SELECT 1');
    $timings['db_connect_select1_ms'] = round((microtime(true) - $dbStart) * 1000, 1);

    // 3) Reuse connection: SELECT 1 again
    $q2 = microtime(true);
    DB::select('SELECT 1');
    $timings['db_reuse_select1_ms'] = round((microtime(true) - $q2) * 1000, 1);

    // 4) Reuse: 3rd SELECT 1
    $q3 = microtime(true);
    DB::select('SELECT 1');
    $timings['db_reuse_select1_2_ms'] = round((microtime(true) - $q3) * 1000, 1);

    $timings['total_ms'] = round((microtime(true) - $start) * 1000, 1);

    return response()->json($timings);
});



require __DIR__ . '/auth.php';
