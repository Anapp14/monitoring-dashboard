<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MonitoringController extends Controller
{
    private $configUrl;
    private $heartbeatUrl;

    public function __construct()
    {
        $slug = env('UPTIME_KUMA_SLUG');
        $base = rtrim(env('UPTIME_KUMA_URL'), '/');
        $this->configUrl = "$base/api/status-page/{$slug}";
        $this->heartbeatUrl = "$base/api/status-page/heartbeat/{$slug}";
    }

    public function index()
    {
        return view('monitoring.index');
    }

    public function getMonitoringData()
    {
        try {
            $configData = json_decode(file_get_contents($this->configUrl), true);
            $heartbeatData = json_decode(file_get_contents($this->heartbeatUrl), true);

            $monitorList = $configData['publicGroupList'][0]['monitorList'];
            $heartbeatList = $heartbeatData['heartbeatList'];
            $uptimeList = $heartbeatData['uptimeList'];

            $summary = ['total' => 0, 'up' => 0, 'down' => 0, 'paused' => 0];
            $monitors = [];
            $dates = [];

            foreach ($monitorList as $monitor) {
                $id = $monitor['id'];
                $name = $monitor['name'];
                $type = $monitor['type'];
                $heartbeats = $heartbeatList[$id] ?? [];

                // Ambil status terakhir
                $latest = end($heartbeats);
                $status = $latest['status'] ?? 2;
                $ping = $latest['ping'] ?? null;
                $time = $latest['time'] ?? null;

                if ($status == 1) $summary['up']++;
                elseif ($status == 0) $summary['down']++;
                else $summary['paused']++;
                $summary['total']++;

                // Hitung uptime per hari dan total 7 hari
                $perDay = [];
                $totalUptimeSum = 0;
                $validDaysCount = 0;
                $days = [];

                for ($i = 0; $i <= 6; $i++) {
                    $carbonDate = Carbon::now()->subDays($i);
                    $displayDate = $carbonDate->format('d M'); // Format yang ditampilkan: "24 Jun"
                    
                    // Cari uptime untuk hari ini berdasarkan format key dari uptimeList
                    $uptimeKey = $id . '_' . (24 - $i); // Asumsi 24 adalah hari ini, 23 kemarin, dst
                    $uptimeValue = 0;
                    
                    // Cari key yang sesuai dengan pola monitor_id + day
                    foreach ($uptimeList as $key => $value) {
                        if (strpos($key, $id . '_') === 0) {
                            $dayPart = (int)str_replace($id . '_', '', $key);
                            if ($dayPart === (24 - $i)) {
                                $uptimeValue = $value;
                                break;
                            }
                        }
                    }
                    
                    $uptimePercent = round($uptimeValue * 100, 2);
                    $perDay[] = $uptimePercent;
                    
                    if ($uptimeValue > 0) {
                        $totalUptimeSum += $uptimeValue;
                        $validDaysCount++;
                    }

                    $days[] = [
                        'date' => $displayDate,
                        'uptime' => $uptimePercent
                    ];
                }

                // Hitung rata-rata 7 hari terakhir
                $average7Days = $validDaysCount > 0 ? round(($totalUptimeSum / $validDaysCount) * 100, 2) : 0;

                // Set tanggal hanya sekali, dalam urutan dari hari ini ke 6 hari ke belakang
                if (empty($dates)) {
                    $dates = array_column($days, 'date');
                }

                $monitors[] = [
                    'id' => $id,
                    'friendly_name' => $name,
                    'status' => $status,
                    'type' => $type,
                    'ping' => $ping,
                    'time' => $time,
                    'last_7_days' => $days,
                    'average_7_days' => $average7Days
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'dates' => $dates,
                    'monitors' => $monitors,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Monitoring Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal server error.']);
        }
    }

    private function groupByDate($heartbeats)
    {
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
}