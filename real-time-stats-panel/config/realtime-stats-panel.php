<?php

/**
 * +--------------------------------------------------------------+
 * |                           ALIMUSA                            |
 * |        RealTimeStatsPanel for Laravel and FilamentPHP        |
 * |     Production-ready real-time dashboard metrics plugin      |
 * +--------------------------------------------------------------+
 * | Author:  Ali Musa                                            |
 * | Website: https://www.alimusa.so/                             |
 * | GitHub:  https://github.com/alimusa80                        |
 * +--------------------------------------------------------------+
 */

use Alimusa\RealTimeStatsPanel\Collectors\ActiveUsersCollector;
use Alimusa\RealTimeStatsPanel\Collectors\RequestsPerSecondCollector;
use Alimusa\RealTimeStatsPanel\Collectors\SystemUsageCollector;

return [
    'ability' => 'viewFilamentRealTimeStats',
    'authorize' => null,
    'broadcast_channel_prefix' => 'filament.realtime-stats',
    'broadcast_event' => 'realtime-stats.snapshot.updated',
    'broadcast_interval_seconds' => 1,
    'request_window_seconds' => 10,
    'heartbeat_ttl_seconds' => 90,
    'history_points' => 20,
    'debounce_ms' => 350,
    'echo_wait_timeout_ms' => 4_000,
    'cache_store' => env('REALTIME_STATS_CACHE_STORE'),
    'cache_prefix' => 'realtime-stats-panel',
    'collectors' => [
        ActiveUsersCollector::class,
        RequestsPerSecondCollector::class,
        SystemUsageCollector::class,
    ],
];
