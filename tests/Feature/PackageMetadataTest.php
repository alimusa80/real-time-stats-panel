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

namespace Alimusa\RealTimeStatsPanel\Tests\Feature;

use Alimusa\RealTimeStatsPanel\RealTimeStatsPanelPlugin;
use Alimusa\RealTimeStatsPanel\Services\RealTimeStatsManager;
use Alimusa\RealTimeStatsPanel\Tests\TestCase;

class PackageMetadataTest extends TestCase
{
    public function test_plugin_and_services_are_resolvable(): void
    {
        $this->assertSame('real-time-stats-panel', RealTimeStatsPanelPlugin::make()->getId());
        $this->assertInstanceOf(RealTimeStatsManager::class, $this->app->make(RealTimeStatsManager::class));
    }
}
