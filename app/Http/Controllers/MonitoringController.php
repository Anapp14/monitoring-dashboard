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

    // Method for month dashboard
    public function monthDashboard()
    {
        return view('monitoring.month');
    }

    // Method for admin dashboard
    public function adminDashboard()
    {
        return view('monitoring.admin');
    }

    // Method for admin dashboard API
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

        foreach ($monitorList as $monitor) {
            $id = $monitor['id'];
            $name = $monitor['name'];
            $type = $monitor['type'];
            $heartbeats = $heartbeatList[$id] ?? [];

            $latest = $this->getLatestHeartbeat($heartbeats);
            $status = $latest['status'] ?? 2;
            $ping = $latest['ping'] ?? null;
            $time = $latest['time'] ?? null;

            // Hitung uptime 7 hari untuk filter
            $uptimeData = $this->calculateUptimeData($id, $heartbeatData['uptimeList'] ?? [], $this->generateDateRange(7));

            // Skip jika rata-rata 7 hari adalah 0%
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

        // --- Tambahkan kode pengurutan di sini ---
        // Mengurutkan array $monitors berdasarkan 'name' secara alfabetis (A-Z)
        usort($monitors, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        // --- Akhir kode pengurutan ---

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'monitors' => $monitors, // Array yang sudah diurutkan
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

    // IMPROVED Method for month monitoring data API (30 days)
    public function getMonthMonitoringData()
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

            // Generate dates for the last 7 days (for display columns)
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

                // Calculate 30 days average from database
                $monthlyUptimeData = $this->calculateMonthlyUptimeData($id, $name, $type);

                // For display columns, still use 7 days data from API
                $weeklyUptimeData = $this->calculateUptimeData($id, $uptimeList, $dateRange);

                Log::info("Monitor {$name}: Average 30 days = {$monthlyUptimeData['average']}%");

                // Filter out monitors with 0% average uptime (30 days)
                if ($monthlyUptimeData['average'] <= 0) {
                    Log::info("Monitor {$name}: Skipping due to 0% average uptime (30 days)");
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
                    'last_7_days' => $weeklyUptimeData['daily'], // For display columns
                    'average_30_days' => $monthlyUptimeData['average'], // Main difference: 30 days average
                    'data_quality' => $monthlyUptimeData['quality']
                ];

                // Save today's data to DB for 30 days calculation
                $this->saveMonthlyData($id, $name, $type, $weeklyUptimeData);
            }

            usort($monitors, function ($a, $b) {
                return strcmp($a['friendly_name'], $b['friendly_name']);
            });

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
            Log::error('Month Monitoring Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Internal server error.',
                'error_code' => 'MONTH_MONITORING_ERROR'
            ], 500);
        }
    }

    // EXISTING Method for weekly monitoring data API (7 days) - NO CHANGES
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

                // Filter out monitors with 0% average uptime
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

                // Save data for future 30-day calculations
                $this->saveWeeklyData($id, $name, $type, $uptimeData);
            }

            usort($monitors, function ($a, $b) {
                return strcmp($a['friendly_name'], $b['friendly_name']);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'dates' => $dates,
                    'monitors' => $monitors, // Array yang sudah diurutkan
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

    // Tambahkan method ini di MonitoringController.php

    // Method for group dashboard
    public function groupDashboard()
    {
        return view('monitoring.group');
    }

    // Method for group dashboard API
    public function getGroupMonitoringData()
    {
        try {
            $configResponse = $this->fetchWithRetry($this->configUrl);
            $heartbeatResponse = $this->fetchWithRetry($this->heartbeatUrl);

            if (!$configResponse || !$heartbeatResponse) {
                throw new \Exception('Failed to fetch monitoring data from Uptime Kuma');
            }

            $configData = json_decode($configResponse, true);
            $heartbeatData = json_decode($heartbeatResponse, true);

            if (!isset($configData['publicGroupList'])) {
                throw new \Exception('Invalid config data structure');
            }

            if (!isset($heartbeatData['heartbeatList'])) {
                throw new \Exception('Invalid heartbeat data structure');
            }

            $publicGroupList = $configData['publicGroupList'];
            $heartbeatList = $heartbeatData['heartbeatList'];
            $uptimeList = $heartbeatData['uptimeList'] ?? [];

            $groups = [];
            $overallSummary = ['total' => 0, 'up' => 0, 'down' => 0, 'paused' => 0, 'maintenance' => 0];

            // Generate date range for 7 days
            $dateRange = $this->generateDateRange(7);

            foreach ($publicGroupList as $group) {
                $groupName = $group['name'] ?? 'Unnamed Group';
                $monitorList = $group['monitorList'] ?? [];

                $groupSummary = ['total' => 0, 'up' => 0, 'down' => 0, 'paused' => 0, 'maintenance' => 0];
                $groupMonitors = [];
                $groupUptimeData = [];

                foreach ($monitorList as $monitor) {
                    $id = $monitor['id'];
                    $name = $monitor['name'];
                    $type = $monitor['type'];
                    $heartbeats = $heartbeatList[$id] ?? [];

                    $latest = $this->getLatestHeartbeat($heartbeats);
                    $status = $latest['status'] ?? 2;
                    $ping = $latest['ping'] ?? null;
                    $time = $latest['time'] ?? null;

                    // Calculate uptime for this monitor
                    $uptimeData = $this->calculateUptimeData($id, $uptimeList, $dateRange);

                    // Skip if average is 0%
                    if ($uptimeData['average'] <= 0) {
                        continue;
                    }

                    $this->updateSummary($groupSummary, $status);
                    $this->updateSummary($overallSummary, $status);

                    $groupMonitors[] = [
                        'id' => $id,
                        'name' => $name,
                        'status' => $status,
                        'status_text' => $this->getStatusText($status),
                        'type' => $type,
                        'ping' => $ping,
                        'time' => $time,
                        'last_7_days' => $uptimeData['daily'],
                        'average_7_days' => $uptimeData['average'],
                        'last_updated' => $time ? Carbon::parse($time)->format('H:i:s') : 'Unknown',
                    ];

                    // Collect uptime data for group average calculation
                    foreach ($uptimeData['daily'] as $dayIndex => $dayData) {
                        if (!isset($groupUptimeData[$dayIndex])) {
                            $groupUptimeData[$dayIndex] = [
                                'date' => $dayData['date'],
                                'total' => 0,
                                'count' => 0
                            ];
                        }
                        if ($dayData['uptime'] > 0) {
                            $groupUptimeData[$dayIndex]['total'] += $dayData['uptime'];
                            $groupUptimeData[$dayIndex]['count']++;
                        }
                    }
                }

                // Calculate group average uptime per day
                $groupDailyUptime = [];
                foreach ($groupUptimeData as $dayData) {
                    $avgUptime = $dayData['count'] > 0 
                        ? round($dayData['total'] / $dayData['count'], 2) 
                        : 0;
                    
                    $groupDailyUptime[] = [
                        'date' => $dayData['date'],
                        'uptime' => $avgUptime
                    ];
                }

                // Calculate overall group average
                $validUptimes = array_filter(array_column($groupDailyUptime, 'uptime'), function($u) {
                    return $u > 0;
                });
                $groupAverage = !empty($validUptimes) 
                    ? round(array_sum($validUptimes) / count($validUptimes), 2) 
                    : 0;

                // Sort monitors alphabetically
                usort($groupMonitors, function ($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });

                // Only add group if it has monitors with data
                if (!empty($groupMonitors)) {
                    $groups[] = [
                        'name' => $groupName,
                        'summary' => $groupSummary,
                        'monitors' => $groupMonitors,
                        'daily_uptime' => $groupDailyUptime,
                        'average_uptime' => $groupAverage
                    ];
                }
            }

            // Sort groups alphabetically
            usort($groups, function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'overall_summary' => $overallSummary,
                    'dates' => array_column($dateRange, 'display'),
                    'groups' => $groups,
                    'last_updated' => Carbon::now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Group Monitoring Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Internal server error.',
                'error_code' => 'GROUP_MONITORING_ERROR'
            ], 500);
        }
    }

    //group
    public function dashboard()
    {
        return view('monitoring.group');
    }

    /**
     * Save weekly data to database (for 7-day dashboard)
     */
    private function saveWeeklyData($monitorId, $name, $type, $uptimeData)
    {
        $today = Carbon::today()->format('Y-m-d');
        $todayUptime = $uptimeData['daily'][0]['raw_value'] ?? null;

        if ($todayUptime !== null) {
            MonitoringRecord::updateOrCreate(
                [
                    'monitor_id' => $monitorId,
                    'date' => $today,
                ],
                [
                    'name' => $name,
                    'type' => $type,
                    'uptime' => round($todayUptime * 100, 2),
                ]
            );
        }

        // Delete data older than 7 days for weekly dashboard
        MonitoringRecord::where('monitor_id', $monitorId)
            ->where('date', '<', Carbon::today()->subDays(6)->format('Y-m-d'))
            ->delete();
    }

    /**
     * Save monthly data to database (for 30-day dashboard)
     */
    private function saveMonthlyData($monitorId, $name, $type, $weeklyUptimeData)
    {
        $today = Carbon::today()->format('Y-m-d');
        $todayUptime = $weeklyUptimeData['daily'][0]['raw_value'] ?? null;

        if ($todayUptime !== null) {
            MonitoringRecord::updateOrCreate(
                [
                    'monitor_id' => $monitorId,
                    'date' => $today,
                ],
                [
                    'name' => $name,
                    'type' => $type,
                    'uptime' => round($todayUptime * 100, 2),
                ]
            );
        }

        // Delete data older than 30 days for monthly dashboard
        MonitoringRecord::where('monitor_id', $monitorId)
            ->where('date', '<', Carbon::today()->subDays(29)->format('Y-m-d'))
            ->delete();
    }

    /**
     * Calculate monthly uptime data from database (30 days)
     */
    private function calculateMonthlyUptimeData($monitorId, $monitorName, $monitorType)
    {
        // Get data from database for the last 30 days
        $records = MonitoringRecord::where('monitor_id', $monitorId)
            ->where('date', '>=', Carbon::today()->subDays(29)->format('Y-m-d'))
            ->orderBy('date', 'desc')
            ->get();

        $validUptimes = [];
        $totalDays = 30;
        $availableDays = $records->count();

        foreach ($records as $record) {
            if ($record->uptime > 0) {
                $validUptimes[] = $record->uptime / 100; // Convert percentage to decimal
            }
        }

        // Calculate average from valid data points
        $average = 0;
        if (!empty($validUptimes)) {
            $average = round((array_sum($validUptimes) / count($validUptimes)) * 100, 2);
        }

        // Determine data quality
        $missingDays = $totalDays - $availableDays;
        $dataQuality = [
            'missing_days' => $missingDays,
            'available_days' => $availableDays,
            'quality_score' => round(($availableDays / $totalDays) * 100, 1),
            'reliable' => $missingDays <= 3, // Consider reliable if missing <= 3 days
            'source' => 'database'
        ];

        Log::info("Monitor {$monitorId}: 30-day average = {$average}% (from {$availableDays}/{$totalDays} days)");

        return [
            'average' => $average,
            'quality' => $dataQuality,
            'total_records' => $availableDays
        ];
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

    /**
     * Calculate uptime data with database fallback (UNIFIED METHOD)
     */
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
}
