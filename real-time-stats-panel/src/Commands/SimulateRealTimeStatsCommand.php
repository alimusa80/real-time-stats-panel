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

namespace Alimusa\RealTimeStatsPanel\Commands;

use Alimusa\RealTimeStatsPanel\Services\RealTimeStatsManager;
use Illuminate\Console\Command;

class SimulateRealTimeStatsCommand extends Command
{
    protected $signature = 'realtime-stats-panel:simulate
        {panel=admin : The Filament panel id}
        {--iterations=30 : Number of snapshots to emit}
        {--sleep=1 : Seconds between snapshots}';

    protected $description = 'Broadcast dummy real-time stats snapshots for dashboard testing.';

    public function handle(RealTimeStatsManager $stats): int
    {
        $panelId = (string) $this->argument('panel');
        $iterations = max(1, (int) $this->option('iterations'));
        $sleep = max(1, (int) $this->option('sleep'));

        $this->components->info("Broadcasting {$iterations} snapshots to panel [{$panelId}]...");

        foreach (range(1, $iterations) as $iteration) {
            $activeUsers = random_int(2, 18);
            $rps = round(max(0.15, 1.2 + sin($iteration / 4) * 1.4 + (random_int(0, 30) / 25)), 2);
            $cpu = round(max(6, min(94, 42 + sin($iteration / 3) * 18 + random_int(-4, 4))), 1);
            $memory = round(max(96, 160 + ($activeUsers * 7.5) + random_int(-12, 16)), 1);

            $stats->storeSnapshot($panelId, [
                'active_users' => [
                    'label' => 'Active users',
                    'value' => $activeUsers,
                    'formatted_value' => number_format($activeUsers),
                    'description' => 'Synthetic demo traffic',
                    'color' => 'success',
                ],
                'requests_per_second' => [
                    'label' => 'Requests / second',
                    'value' => $rps,
                    'formatted_value' => number_format($rps, 2),
                    'suffix' => 'RPS',
                    'description' => 'Synthetic demo traffic',
                    'color' => 'primary',
                ],
                'cpu_usage' => [
                    'label' => 'CPU usage',
                    'value' => $cpu,
                    'formatted_value' => number_format($cpu, 1),
                    'suffix' => '%',
                    'description' => 'Synthetic demo load',
                    'color' => $cpu >= 75 ? 'danger' : ($cpu >= 55 ? 'warning' : 'gray'),
                ],
                'memory_usage' => [
                    'label' => 'Memory usage',
                    'value' => $memory,
                    'formatted_value' => number_format($memory, 1),
                    'suffix' => 'MB',
                    'description' => 'Synthetic demo footprint',
                    'color' => $memory >= 512 ? 'warning' : 'gray',
                ],
            ]);

            $this->components->twoColumnDetail('Snapshot', "#{$iteration} emitted");

            if ($iteration < $iterations) {
                sleep($sleep);
            }
        }

        $this->components->info('Simulation finished.');

        return self::SUCCESS;
    }
}
