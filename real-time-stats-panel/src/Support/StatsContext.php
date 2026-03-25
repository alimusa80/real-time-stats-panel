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

namespace Alimusa\RealTimeStatsPanel\Support;

final readonly class StatsContext
{
    /**
     * @param  array<string, float>  $activeUsers
     * @param  array<int, float>  $requestTimestamps
     */
    public function __construct(
        public string $panelId,
        public float $capturedAt,
        public array $activeUsers,
        public array $requestTimestamps,
        public int $requestWindowSeconds,
        public int $heartbeatTtlSeconds,
    ) {
    }

    public function activeUserCount(): int
    {
        return count($this->activeUsers);
    }

    public function requestsPerSecond(): float
    {
        return round(count($this->requestTimestamps) / max(1, $this->requestWindowSeconds), 2);
    }

    public function processMemoryUsageMb(): float
    {
        return round(memory_get_usage(true) / 1024 / 1024, 1);
    }
}
