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

namespace Alimusa\RealTimeStatsPanel\Widgets;

use Alimusa\RealTimeStatsPanel\Services\RealTimeStatsManager;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class RealTimeStatsPanelWidget extends Widget
{
    protected string $view = 'real-time-stats-panel::widgets.real-time-stats-panel-widget';

    protected static ?int $sort = 5;

    public int | string | array $configuredColumnSpan = 'full';

    public string $pollingInterval = '';

    /**
     * @var array<string, mixed>
     */
    public array $snapshot = [];

    public function mount(RealTimeStatsManager $stats): void
    {
        $this->snapshot = $stats->snapshot(Filament::getCurrentPanel());
    }

    public static function canView(): bool
    {
        return app(RealTimeStatsManager::class)->authorize(
            Filament::auth()->user(),
            Filament::getCurrentPanel(),
        );
    }

    public function refreshSnapshot(RealTimeStatsManager $stats): void
    {
        $this->snapshot = $stats->snapshot(Filament::getCurrentPanel());
    }

    public function getColumnSpan(): int | string | array
    {
        return $this->configuredColumnSpan;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $stats = app(RealTimeStatsManager::class);
        $panel = Filament::getCurrentPanel();

        return [
            'broadcastChannel' => $stats->broadcastChannelName($panel),
            'broadcastEvent' => $stats->broadcastEventName(),
            'debounceMs' => (int) config('realtime-stats-panel.debounce_ms', 350),
            'echoWaitTimeoutMs' => (int) config('realtime-stats-panel.echo_wait_timeout_ms', 4000),
            'pollingInterval' => $this->pollingInterval,
            'snapshot' => $this->snapshot,
        ];
    }
}
