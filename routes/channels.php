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

use Alimusa\RealTimeStatsPanel\Services\RealTimeStatsManager;
use Illuminate\Support\Facades\Broadcast;

$prefix = trim((string) config('realtime-stats-panel.broadcast_channel_prefix', 'filament.realtime-stats'), '.');

Broadcast::channel("{$prefix}.{panelId}", function ($user, string $panelId): bool {
    return app(RealTimeStatsManager::class)->authorize($user, $panelId);
});
