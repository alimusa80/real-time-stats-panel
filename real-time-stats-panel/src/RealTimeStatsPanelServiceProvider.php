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

use Alimusa\RealTimeStatsPanel\Commands\SimulateRealTimeStatsCommand;
use Alimusa\RealTimeStatsPanel\Events\StatsSnapshotCollected;
use Alimusa\RealTimeStatsPanel\Listeners\BroadcastStatsSnapshot;
use Alimusa\RealTimeStatsPanel\Services\RealTimeStatsManager;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RealTimeStatsPanelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('real-time-stats-panel')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommands([
                SimulateRealTimeStatsCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(RealTimeStatsManager::class);
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Js::make('realtime-stats-panel', __DIR__ . '/../resources/dist/realtime-stats-panel.js'),
        ], 'alimusa/real-time-stats-panel');

        Event::listen(StatsSnapshotCollected::class, BroadcastStatsSnapshot::class);

        $this->registerDefaultGate();

        require_once __DIR__ . '/../routes/channels.php';
    }

    protected function registerDefaultGate(): void
    {
        $ability = trim((string) config('realtime-stats-panel.ability', 'viewFilamentRealTimeStats'));

        if (($ability === '') || Gate::has($ability)) {
            return;
        }

        Gate::define($ability, function (Authenticatable $user, mixed $panel = null): bool {
            return $this->app->make(RealTimeStatsManager::class)->baseAuthorization($user, $panel);
        });
    }
}
