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

namespace Alimusa\RealTimeStatsPanel\Listeners;

use Alimusa\RealTimeStatsPanel\Events\StatsPanelUpdated;
use Alimusa\RealTimeStatsPanel\Events\StatsSnapshotCollected;

class BroadcastStatsSnapshot
{
    public function handle(StatsSnapshotCollected $event): void
    {
        broadcast(new StatsPanelUpdated($event->panelId, $event->snapshot));
    }
}
