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

class SystemUsageCollector implements StatsCollector
{
    public function collect(StatsContext $context): array
    {
        $cpuUsage = $this->resolveCpuUsage($context);
        $memoryUsage = $this->resolveMemoryUsage($context);

        return [
            'cpu_usage' => [
                'label' => 'CPU usage',
                'value' => $cpuUsage,
                'formatted_value' => number_format($cpuUsage, 1),
                'suffix' => '%',
                'description' => 'Synthetic load sample',
                'color' => $cpuUsage >= 75 ? 'danger' : ($cpuUsage >= 55 ? 'warning' : 'gray'),
            ],
            'memory_usage' => [
                'label' => 'Memory usage',
                'value' => $memoryUsage,
                'formatted_value' => number_format($memoryUsage, 1),
                'suffix' => 'MB',
                'description' => 'PHP worker footprint',
                'color' => $memoryUsage >= 512 ? 'warning' : 'gray',
            ],
        ];
    }

    protected function resolveCpuUsage(StatsContext $context): float
    {
        $synthetic = 36
            + (sin($context->capturedAt / 7) * 16)
            + ($context->activeUserCount() * 1.8)
            + ($context->requestsPerSecond() * 4.5);

        return round(max(4, min(95, $synthetic)), 1);
    }

    protected function resolveMemoryUsage(StatsContext $context): float
    {
        $usage = $context->processMemoryUsageMb() + ($context->activeUserCount() * 2.5);

        return round($usage, 1);
    }
}
