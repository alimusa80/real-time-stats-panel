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

namespace Alimusa\RealTimeStatsPanel;

use Alimusa\RealTimeStatsPanel\Http\Middleware\TrackRealTimeStats;
use Alimusa\RealTimeStatsPanel\Widgets\RealTimeStatsPanelWidget;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;

class RealTimeStatsPanelPlugin implements Plugin
{
    public const ID = 'real-time-stats-panel';

    protected bool $registerWidget = true;

    protected int | string | array $columnSpan = 'full';

    protected string $pollingInterval = '';

    protected ?Closure $authorizeUsing = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function register(Panel $panel): void
    {
        $panel->middleware([TrackRealTimeStats::class], isPersistent: true);

        if (! $this->registerWidget) {
            return;
        }

        $panel->widgets([
            RealTimeStatsPanelWidget::make([
                'configuredColumnSpan' => $this->columnSpan,
                'pollingInterval' => $this->pollingInterval,
            ]),
        ]);
    }

    public function boot(Panel $panel): void
    {
        // Nothing else to boot after registration.
    }

    public function registerWidget(bool $condition = true): static
    {
        $this->registerWidget = $condition;

        return $this;
    }

    public function columnSpan(int | string | array $columnSpan): static
    {
        $this->columnSpan = $columnSpan;

        return $this;
    }

    public function pollingInterval(string $pollingInterval): static
    {
        $this->pollingInterval = $pollingInterval;

        return $this;
    }

    public function authorizeUsing(?Closure $callback): static
    {
        $this->authorizeUsing = $callback;

        return $this;
    }

    public function getAuthorizeUsing(): ?Closure
    {
        return $this->authorizeUsing;
    }
}
