<?php

use App\Http\Controllers\MonitoringController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [MonitoringController::class, 'index'])->name('monitoring.index');
Route::get('/api/monitoring', [MonitoringController::class, 'getMonitoringData'])->name('monitoring.data');
//Admin dashboard
Route::get('/admin-dashboard', [MonitoringController::class, 'adminDashboard'])->name('monitoring.admin');
Route::get('/api/test', function () {
    function groupByDate($heartbeats) {
    $result = [];

    foreach ($heartbeats as $entry) {
        $date = substr($entry['time'], 0, 10); // Format YYYY-MM-DD
        if (!isset($result[$date])) {
            $result[$date] = [];
        }
        $result[$date][] = $entry;
    }

    return $result;
}

// $configUrl = 'https://uptimekuma.ainosi.com/api/status-page/demo';
// $heartbeatUrl = 'https://uptimekuma.ainosi.com/api/status-page/heartbeat/demo';

// testing
$configUrl = 'http://10.10.10.110:3001/api/status-page/rumah';
$heartbeatUrl = 'http://10.10.10.110:3001/api/status-page/heartbeat/rumah';

$configData = json_decode(file_get_contents($configUrl), true);
$heartbeatData = json_decode(file_get_contents($heartbeatUrl), true);

$monitorList = $configData['publicGroupList'][0]['monitorList'];

$monitorMerged = [];

foreach ($monitorList as $monitor) {
    $id = $monitor['id'];
    $heartbeats = $heartbeatData['heartbeatList'][$id] ?? [];

    // Ambil status terakhir
    $latest = end($heartbeats);
    $status = $latest['status'] ?? null;
    $ping = $latest['ping'] ?? null;
    $time = $latest['time'] ?? null;

    // Uptime 24 jam
    $uptimeKey = "{$id}_24";
    $uptime = $heartbeatData['uptimeList'][$uptimeKey] ?? null;

    // Group heartbeat by date
    $groupedByDate = groupByDate($heartbeats);

    // Hitung per hari dan total 7 hari
    $perDay = [];
    $totalLast7Days = 0;

    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $count = isset($groupedByDate[$date]) ? count($groupedByDate[$date]) : 0;
        $perDay[$date] = $count;
        $totalLast7Days += $count;
    }

    krsort($perDay); // agar urutan dari hari terbaru ke terlama

    $monitorMerged[] = [
        'id' => $id,
        'name' => $monitor['name'],
        'type' => $monitor['type'],
        'status' => $status,
        'ping' => $ping,
        'time' => $time,
        'uptime_24h' => $uptime,
        'per_day' => $perDay,                     // <== Data per hari
        'total_last_7_days' => $totalLast7Days    // <== Totalnya
    ];
}

    dd($monitorMerged);
});
