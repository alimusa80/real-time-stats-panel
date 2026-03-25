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

namespace Alimusa\RealTimeStatsPanel\Collectors;

use Alimusa\RealTimeStatsPanel\Contracts\StatsCollector;
use Alimusa\RealTimeStatsPanel\Support\StatsContext;

class ActiveUsersCollector implements StatsCollector
{
    public function collect(StatsContext $context): array
    {
        $count = $context->activeUserCount();

        return [
            'active_users' => [
                'label' => 'Active users',
                'value' => $count,
                'formatted_value' => number_format($count),
                'suffix' => null,
                'description' => "Seen in the last {$context->heartbeatTtlSeconds}s",
                'color' => 'success',
            ],
        ];
    }
}
