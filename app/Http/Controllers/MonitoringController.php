<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\MonitoringRecord;

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

    // New method for admin dashboard
    public function adminDashboard()
    {
        return view('monitoring.admin');
    }

    // New method for admin dashboard API
    public function getAdminMonitoringData()
    {
        try {
            $configResponse = $this->fetchWithRetry($this->configUrl);
            $heartbeatResponse = $this->fetchWithRetry($this->heartbeatUrl);

            if (!$configResponse || !$heartbeatResponse) {
                throw new \Exception('Failed to fetch monitoring data from Uptime Kuma');
            }

            $configData = json_decode($configResponse, true);
            $heartbeatData = json_decode($heartbeatResponse, true);

            if (!isset($configData['publicGroupList'][0]['monitorList'])) {
                throw new \Exception('Invalid config data structure');
            }

            if (!isset($heartbeatData['heartbeatList'])) {
                throw new \Exception('Invalid heartbeat data structure');
            }

            $monitorList = $configData['publicGroupList'][0]['monitorList'];
            $heartbeatList = $heartbeatData['heartbeatList'];

            $summary = ['total' => 0, 'up' => 0, 'down' => 0, 'paused' => 0, 'maintenance' => 0];
            $monitors = [];

            // foreach ($monitorList as $monitor) {
            //     $id = $monitor['id'];
            //     $name = $monitor['name'];
            //     $type = $monitor['type'];
            //     $heartbeats = $heartbeatList[$id] ?? [];

            //     $latest = $this->getLatestHeartbeat($heartbeats);
            //     $status = $latest['status'] ?? 2;
            //     $ping = $latest['ping'] ?? null;
            //     $time = $latest['time'] ?? null;

            //     $this->updateSummary($summary, $status);

            //     $monitors[] = [
            //         'id' => $id,
            //         'name' => $name,
            //         'status' => $status,
            //         'status_text' => $this->getStatusText($status),
            //         'type' => $type,
            //         'ping' => $ping,
            //         'time' => $time,
            //         'last_updated' => $time ? Carbon::parse($time)->format('H:i:s') : 'Unknown'
            //     ];
            // }

            foreach ($monitorList as $monitor) {
                $id = $monitor['id'];
                $name = $monitor['name'];
                $type = $monitor['type'];
                $heartbeats = $heartbeatList[$id] ?? [];

                $latest = $this->getLatestHeartbeat($heartbeats);
                $status = $latest['status'] ?? 2;
                $ping = $latest['ping'] ?? null;
                $time = $latest['time'] ?? null;

                // 🧠 Hitung uptime 7 hari (gunakan fungsi yg sudah ada)
                $uptimeData = $this->calculateUptimeData($id, $heartbeatData['uptimeList'], $this->generateDateRange(7));

                // 🔥 Skip jika rata-rata 7 hari adalah 0%
                if ($uptimeData['average'] <= 0) {
                    continue;
                }

                $this->updateSummary($summary, $status);

                $monitors[] = [
                    'id' => $id,
                    'name' => $name,
                    'status' => $status,
                    'status_text' => $this->getStatusText($status),
                    'type' => $type,
                    'ping' => $ping,
                    'time' => $time,
                    'last_updated' => $time ? Carbon::parse($time)->format('H:i:s') : 'Unknown',
                ];
            }


            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'monitors' => $monitors,
                    'last_updated' => Carbon::now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Admin Monitoring Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false, 
                'message' => 'Internal server error.',
                'error_code' => 'ADMIN_MONITORING_ERROR'
            ], 500);
        }
    }

    public function getMonitoringData()
    {
        try {
            // Fetch data with better error handling
            $configResponse = $this->fetchWithRetry($this->configUrl);
            $heartbeatResponse = $this->fetchWithRetry($this->heartbeatUrl);

            if (!$configResponse || !$heartbeatResponse) {
                throw new \Exception('Failed to fetch monitoring data from Uptime Kuma');
            }

            $configData = json_decode($configResponse, true);
            $heartbeatData = json_decode($heartbeatResponse, true);

            // Validate response structure
            if (!isset($configData['publicGroupList'][0]['monitorList'])) {
                throw new \Exception('Invalid config data structure');
            }

            if (!isset($heartbeatData['heartbeatList']) || !isset($heartbeatData['uptimeList'])) {
                throw new \Exception('Invalid heartbeat data structure');
            }

            $monitorList = $configData['publicGroupList'][0]['monitorList'];
            $heartbeatList = $heartbeatData['heartbeatList'];
            $uptimeList = $heartbeatData['uptimeList'];

            $summary = ['total' => 0, 'up' => 0, 'down' => 0, 'paused' => 0, 'maintenance' => 0];
            $monitors = [];
            $dates = [];

            // Generate dates for the last 7 days (today to 6 days ago)
            $dateRange = $this->generateDateRange(7);
            $dates = array_column($dateRange, 'display');

            foreach ($monitorList as $monitor) {
                $id = $monitor['id'];
                $name = $monitor['name'];
                $type = $monitor['type'];
                $heartbeats = $heartbeatList[$id] ?? [];

                Log::info("Processing monitor: {$name} (ID: {$id})");

                $latest = $this->getLatestHeartbeat($heartbeats);
                $status = $latest['status'] ?? 2;
                $ping = $latest['ping'] ?? null;
                $time = $latest['time'] ?? null;

                $uptimeData = $this->calculateUptimeData($id, $uptimeList, $dateRange);

                Log::info("Monitor {$name}: Average 7 days = {$uptimeData['average']}%");

                // 🔥 Filter out monitors with 0% average uptime
                if ($uptimeData['average'] <= 0) {
                    Log::info("Monitor {$name}: Skipping due to 0% average uptime");
                    continue;
                }

                $this->updateSummary($summary, $status);

                $monitors[] = [
                    'id' => $id,
                    'friendly_name' => $name,
                    'status' => $status,
                    'status_text' => $this->getStatusText($status),
                    'type' => $type,
                    'ping' => $ping,
                    'time' => $time,
                    'last_7_days' => $uptimeData['daily'],
                    'average_7_days' => $uptimeData['average'],
                    'data_quality' => $uptimeData['quality']
                ];

                // ✅ Tambahan: Simpan data ke DB
                $today = Carbon::today()->format('Y-m-d');
                $todayUptime = $uptimeData['daily'][0]['raw_value'] ?? null;

                if ($todayUptime !== null) {
                    MonitoringRecord::updateOrCreate(
                        [
                            'monitor_id' => $id,
                            'date' => $today,
                        ],
                        [
                            'name' => $name,
                            'type' => $type,
                            'uptime' => round($todayUptime * 100, 2),
                        ]
                    );
                }

                // ✅ Tambahan: Hapus data lama (> 7 hari)
                MonitoringRecord::where('monitor_id', $id)
                    ->where('date', '<', Carbon::today()->subDays(6)->format('Y-m-d'))
                    ->delete();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'dates' => $dates,
                    'monitors' => $monitors,
                    'last_updated' => Carbon::now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Monitoring Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false, 
                'message' => 'Internal server error.',
                'error_code' => 'MONITORING_ERROR'
            ], 500);
        }
    }

    /**
     * Fetch URL with retry mechanism
     */
    private function fetchWithRetry($url, $maxRetries = 3)
    {
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            try {
                $content = file_get_contents($url);
                if ($content !== false) {
                    return $content;
                }
            } catch (\Exception $e) {
                Log::warning("Fetch attempt " . ($attempt + 1) . " failed for $url: " . $e->getMessage());
            }
            
            $attempt++;
            if ($attempt < $maxRetries) {
                sleep(1); // Wait 1 second before retry
            }
        }
        
        return false;
    }

    /**
     * Generate date range for the last N days
     */
    private function generateDateRange($days = 7)
    {
        $dateRange = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::today()->subDays($i);
            $dateRange[] = [
                'carbon' => $date,
                'display' => $date->format('d M'),
                'full' => $date->format('Y-m-d'),
                'day_offset' => $i
            ];
        }
        
        return $dateRange;
    }

    /**
     * Get latest heartbeat with validation
     */
    private function getLatestHeartbeat($heartbeats)
    {
        if (empty($heartbeats)) {
            return ['status' => 2, 'ping' => null, 'time' => null];
        }

        // Sort by time to get the latest
        usort($heartbeats, function($a, $b) {
            return strcmp($b['time'] ?? '', $a['time'] ?? '');
        });

        return $heartbeats[0];
    }

    /**
     * Update summary statistics
     */
    private function updateSummary(&$summary, $status)
    {
        switch ($status) {
            case 1:
                $summary['up']++;
                break;
            case 0:
                $summary['down']++;
                break;
            case 2:
                $summary['paused']++;
                break;
            case 3:
                $summary['maintenance']++;
                break;
            default:
                $summary['paused']++;
        }
        $summary['total']++;
    }

    /**
     * Get human-readable status text
     */
    private function getStatusText($status)
    {
        switch ($status) {
            case 1: return 'Up';
            case 0: return 'Down';
            case 2: return 'Paused';
            case 3: return 'Maintenance';
            default: return 'Unknown';
        }
    }

    // Calculate uptime data with database fallback
    
    private function calculateUptimeData($monitorId, $uptimeList, $dateRange)
    {
        $dailyData = [];
        $validUptimes = [];
        $missingDataCount = 0;

        // Find all uptime keys for this monitor from API
        $monitorUptimeKeys = array_filter(array_keys($uptimeList), function($key) use ($monitorId) {
            return strpos($key, $monitorId . '_') === 0;
        });

        // Extract day indices and find the pattern
        $dayIndices = [];
        foreach ($monitorUptimeKeys as $key) {
            $dayIndex = (int)str_replace($monitorId . '_', '', $key);
            $dayIndices[] = $dayIndex;
        }

        $maxDayIndex = 0;
        if (!empty($dayIndices)) {
            rsort($dayIndices); // Sort descending to get latest first
            $maxDayIndex = max($dayIndices);
            
            Log::info("Monitor {$monitorId}: Found day indices: " . implode(', ', $dayIndices));
            Log::info("Monitor {$monitorId}: Max day index: {$maxDayIndex}");
        } else {
            Log::warning("Monitor {$monitorId}: No uptime data found in API");
        }

        // Process each day in the date range
        foreach ($dateRange as $dateInfo) {
            $dayOffset = $dateInfo['day_offset'];
            $displayDate = $dateInfo['display'];
            $dateString = $dateInfo['full']; // Y-m-d format
            
            // Calculate expected day index for API
            $expectedDayIndex = $maxDayIndex - $dayOffset;
            $uptimeKey = $monitorId . '_' . $expectedDayIndex;
            
            $uptimeValue = null;
            $dataStatus = 'missing';
            $dataSource = 'none';
            
            // 1. Try to get from API first
            if (isset($uptimeList[$uptimeKey])) {
                $uptimeValue = $uptimeList[$uptimeKey];
                $dataStatus = 'available';
                $dataSource = 'api';
                
                Log::info("Monitor {$monitorId}: Using API data for {$dateString} = {$uptimeValue}");
            } else {
                // 2. Fallback to database if API data not available
                $dbRecord = MonitoringRecord::where('monitor_id', $monitorId)
                    ->where('date', $dateString)
                    ->first();
                    
                if ($dbRecord) {
                    $uptimeValue = $dbRecord->uptime / 100; // Convert percentage back to decimal
                    $dataStatus = 'available';
                    $dataSource = 'database';
                    
                    Log::info("Monitor {$monitorId}: Using DB data for {$dateString} = {$uptimeValue} (from {$dbRecord->uptime}%)");
                } else {
                    // 3. No data available anywhere
                    $uptimeValue = 0;
                    $dataStatus = 'missing';
                    $dataSource = 'none';
                    $missingDataCount++;
                    
                    Log::warning("Monitor {$monitorId}: No data found for {$dateString} (API key: {$uptimeKey})");
                }
            }

            // Only count as valid uptime if > 0
            if ($uptimeValue > 0) {
                $validUptimes[] = $uptimeValue;
            }

            $uptimePercent = round($uptimeValue * 100, 2);
            
            $dailyData[] = [
                'date' => $displayDate,
                'uptime' => $uptimePercent,
                'status' => $dataStatus,
                'source' => $dataSource, // Track data source
                'raw_value' => $uptimeValue
            ];
        }

        // Calculate average from valid data points
        $average = 0;
        if (!empty($validUptimes)) {
            $average = round((array_sum($validUptimes) / count($validUptimes)) * 100, 2);
        }

        // Determine data quality
        $totalDays = count($dateRange);
        $availableDays = $totalDays - $missingDataCount;
        $dataQuality = [
            'missing_days' => $missingDataCount,
            'available_days' => $availableDays,
            'quality_score' => round(($availableDays / $totalDays) * 100, 1),
            'reliable' => $missingDataCount <= 1, // Consider reliable if missing <= 1 day
            'sources' => $this->getDataSourceSummary($dailyData)
        ];

        Log::info("Monitor {$monitorId}: Final average = {$average}% (from {$availableDays}/{$totalDays} days)");

        return [
            'daily' => $dailyData,
            'average' => $average,
            'quality' => $dataQuality
        ];
    }

    /**
     * Get summary of data sources used
     */
    private function getDataSourceSummary($dailyData)
    {
        $sources = ['api' => 0, 'database' => 0, 'none' => 0];
        
        foreach ($dailyData as $day) {
            $sources[$day['source']]++;
        }
        
        return $sources;
    }

    /**
     * Find nearest available uptime key
     */
    private function findNearestUptimeKey($monitorId, $targetIndex, $availableIndices)
    {
        if (empty($availableIndices)) {
            return null;
        }

        $nearestIndex = null;
        $minDistance = PHP_INT_MAX;

        foreach ($availableIndices as $index) {
            $distance = abs($index - $targetIndex);
            if ($distance < $minDistance && $distance <= 2) { // Max 2 days difference
                $minDistance = $distance;
                $nearestIndex = $index;
            }
        }

        return $nearestIndex ? $monitorId . '_' . $nearestIndex : null;
    }

    /**
     * Legacy method - kept for backward compatibility
     */
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