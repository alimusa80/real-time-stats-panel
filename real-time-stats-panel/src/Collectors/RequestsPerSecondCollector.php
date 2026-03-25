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

class RequestsPerSecondCollector implements StatsCollector
{
    public function collect(StatsContext $context): array
    {
        $value = $context->requestsPerSecond();

        return [
            'requests_per_second' => [
                'label' => 'Requests / second',
                'value' => $value,
                'formatted_value' => number_format($value, 2),
                'suffix' => 'RPS',
                'description' => "{$context->requestWindowSeconds}s rolling window",
                'color' => 'primary',
            ],
        ];
    }
}
