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

namespace Alimusa\RealTimeStatsPanel\Http\Middleware;

use Alimusa\RealTimeStatsPanel\Services\RealTimeStatsManager;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackRealTimeStats
{
    public function __construct(
        protected RealTimeStatsManager $stats,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $panel = Filament::getCurrentPanel();
        $user = Filament::auth()->user() ?? $request->user();

        if (($panel !== null) && ($user !== null)) {
            $this->stats->capture($panel, $user, broadcast: true);
        }

        return $response;
    }
}
