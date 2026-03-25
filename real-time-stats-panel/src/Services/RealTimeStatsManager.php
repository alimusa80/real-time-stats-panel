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

namespace Alimusa\RealTimeStatsPanel\Services;

use Alimusa\RealTimeStatsPanel\Contracts\StatsCollector;
use Alimusa\RealTimeStatsPanel\Events\StatsSnapshotCollected;
use Alimusa\RealTimeStatsPanel\RealTimeStatsPanelPlugin;
use Alimusa\RealTimeStatsPanel\Support\StatsContext;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\CarbonImmutable;
use Illuminate\Support\Facades\Gate;

class RealTimeStatsManager
{
    public function __construct(
        protected CacheFactory $cache,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function capture(Panel | string | null $panel = null, ?Authenticatable $user = null, bool $broadcast = true): array
    {
        $panelId = $this->resolvePanelId($panel);

        if ($panelId === null) {
            return $this->emptySnapshot();
        }

        $result = $this->withLock($panelId, function () use ($panelId, $user, $broadcast): array {
            $snapshot = $this->buildSnapshot($panelId, $user, appendRequest: true);

            return [
                'snapshot' => $snapshot,
                'shouldBroadcast' => $broadcast && $this->markBroadcastIfDue($panelId, microtime(true)),
            ];
        });

        if ($result['shouldBroadcast']) {
            event(new StatsSnapshotCollected($panelId, $result['snapshot']));
        }

        return $result['snapshot'];
    }

    /**
     * @param  array<string, array<string, mixed>>  $metrics
     * @return array<string, mixed>
     */
    public function storeSnapshot(Panel | string $panel, array $metrics, bool $broadcast = true): array
    {
        $panelId = $this->resolvePanelId($panel);

        if ($panelId === null) {
            return $this->emptySnapshot();
        }

        $result = $this->withLock($panelId, function () use ($panelId, $metrics, $broadcast): array {
            $snapshot = $this->buildManualSnapshot($panelId, $metrics);

            return [
                'snapshot' => $snapshot,
                'shouldBroadcast' => $broadcast && $this->markBroadcastIfDue($panelId, microtime(true)),
            ];
        });

        if ($result['shouldBroadcast']) {
            event(new StatsSnapshotCollected($panelId, $result['snapshot']));
        }

        return $result['snapshot'];
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(Panel | string | null $panel = null): array
    {
        $panelId = $this->resolvePanelId($panel);

        if ($panelId === null) {
            return $this->emptySnapshot();
        }

        $snapshot = $this->store()->get($this->cacheKey($panelId, 'snapshot'));

        if (is_array($snapshot) && $snapshot !== []) {
            return $snapshot;
        }

        return $this->withLock($panelId, fn (): array => $this->buildSnapshot($panelId, null, appendRequest: false));
    }

    public function authorize(?Authenticatable $user, Panel | string | null $panel = null): bool
    {
        if ($user === null) {
            return false;
        }

        $panelId = $this->resolvePanelId($panel);

        if ($panelId === null) {
            return false;
        }

        $ability = trim((string) config('realtime-stats-panel.ability', 'viewFilamentRealTimeStats'));
        $panelArgument = $this->resolvePanel($panel) ?? $panelId;

        if (($ability !== '') && Gate::has($ability)) {
            return Gate::forUser($user)->check($ability, [$panelArgument]);
        }

        return $this->baseAuthorization($user, $panelId);
    }

    public function baseAuthorization(?Authenticatable $user, Panel | string | null $panel = null): bool
    {
        $resolvedPanel = $this->resolvePanel($panel);

        if (($user === null) || ($resolvedPanel === null)) {
            return false;
        }

        if (($user instanceof FilamentUser) && (! $user->canAccessPanel($resolvedPanel))) {
            return false;
        }

        $plugin = $this->resolvePlugin($resolvedPanel);

        if (($plugin !== null) && ($callback = $plugin->getAuthorizeUsing())) {
            return (bool) app()->call($callback, [
                'user' => $user,
                'panel' => $resolvedPanel,
            ]);
        }

        $callback = config('realtime-stats-panel.authorize');

        if (is_callable($callback)) {
            return (bool) app()->call($callback, [
                'user' => $user,
                'panel' => $resolvedPanel,
            ]);
        }

        return true;
    }

    public function broadcastChannelName(Panel | string | null $panel = null): string
    {
        $panelId = $this->resolvePanelId($panel) ?? 'default';
        $prefix = trim((string) config('realtime-stats-panel.broadcast_channel_prefix', 'filament.realtime-stats'), '.');

        return "{$prefix}.{$panelId}";
    }

    public function broadcastEventName(): string
    {
        return (string) config('realtime-stats-panel.broadcast_event', 'realtime-stats.snapshot.updated');
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSnapshot(string $panelId, ?Authenticatable $user, bool $appendRequest): array
    {
        [$activeUsers, $requests, $capturedAt] = $this->updateTrackedState($panelId, $user, $appendRequest);

        $context = new StatsContext(
            panelId: $panelId,
            capturedAt: $capturedAt,
            activeUsers: $activeUsers,
            requestTimestamps: $requests,
            requestWindowSeconds: $this->requestWindowSeconds(),
            heartbeatTtlSeconds: $this->heartbeatTtlSeconds(),
        );

        $metrics = [];

        foreach ((array) config('realtime-stats-panel.collectors', []) as $collectorClass) {
            $collector = app($collectorClass);

            if (! $collector instanceof StatsCollector) {
                continue;
            }

            $metrics = [
                ...$metrics,
                ...$collector->collect($context),
            ];
        }

        return $this->persistSnapshot($panelId, $metrics, $capturedAt);
    }

    /**
     * @param  array<string, array<string, mixed>>  $metrics
     * @return array<string, mixed>
     */
    protected function buildManualSnapshot(string $panelId, array $metrics): array
    {
        return $this->persistSnapshot($panelId, $metrics, microtime(true));
    }

    /**
     * @param  array<string, array<string, mixed>>  $metrics
     * @return array<string, mixed>
     */
    protected function persistSnapshot(string $panelId, array $metrics, float $capturedAt): array
    {
        $metrics = $this->appendHistory($panelId, $metrics);

        $snapshot = [
            'panel_id' => $panelId,
            'generated_at' => CarbonImmutable::createFromTimestampUTC((int) floor($capturedAt))->toIso8601String(),
            'metrics' => $metrics,
            'meta' => [
                'heartbeat_ttl_seconds' => $this->heartbeatTtlSeconds(),
                'request_window_seconds' => $this->requestWindowSeconds(),
                'history_points' => $this->historyPoints(),
            ],
        ];

        $this->store()->put(
            $this->cacheKey($panelId, 'snapshot'),
            $snapshot,
            now()->addMinutes(30),
        );

        return $snapshot;
    }

    /**
     * @param  array<string, array<string, mixed>>  $metrics
     * @return array<string, array<string, mixed>>
     */
    protected function appendHistory(string $panelId, array $metrics): array
    {
        $storedHistory = $this->store()->get($this->cacheKey($panelId, 'history'), []);
        $storedHistory = is_array($storedHistory) ? $storedHistory : [];

        foreach ($metrics as $metricKey => $metric) {
            $series = $storedHistory[$metricKey] ?? [];
            $series = is_array($series) ? array_values(array_filter($series, 'is_numeric')) : [];
            $series[] = (float) ($metric['value'] ?? 0);
            $series = array_slice($series, -$this->historyPoints());

            $storedHistory[$metricKey] = $series;
            $metrics[$metricKey] = $this->normalizeMetric($metricKey, $metric, $series);
        }

        $this->store()->put(
            $this->cacheKey($panelId, 'history'),
            $storedHistory,
            now()->addHours(6),
        );

        return $metrics;
    }

    /**
     * @param  array<string, mixed>  $metric
     * @param  array<int, float|int>  $history
     * @return array<string, mixed>
     */
    protected function normalizeMetric(string $metricKey, array $metric, array $history): array
    {
        $value = $metric['value'] ?? 0;
        $formattedValue = $metric['formatted_value'] ?? (is_numeric($value) ? number_format((float) $value, 0) : (string) $value);

        return [
            'key' => $metricKey,
            'label' => $metric['label'] ?? str($metricKey)->headline()->toString(),
            'value' => $value,
            'formatted_value' => (string) $formattedValue,
            'suffix' => $metric['suffix'] ?? null,
            'description' => $metric['description'] ?? null,
            'color' => $metric['color'] ?? 'gray',
            'history' => $history,
            'trend' => $this->trendLabel($history),
        ];
    }

    /**
     * @param  array<int, float|int>  $history
     */
    protected function trendLabel(array $history): string
    {
        if (count($history) < 2) {
            return 'Waiting for more samples';
        }

        $first = (float) $history[0];
        $last = (float) $history[array_key_last($history)];
        $delta = $last - $first;

        if (abs($delta) < 0.01) {
            return 'Stable';
        }

        return $delta > 0 ? 'Trending up' : 'Trending down';
    }

    /**
     * @return array{0: array<string, float>, 1: array<int, float>, 2: float}
     */
    protected function updateTrackedState(string $panelId, ?Authenticatable $user, bool $appendRequest): array
    {
        $capturedAt = microtime(true);
        $expiresBefore = $capturedAt - $this->heartbeatTtlSeconds();

        $activeUsers = $this->store()->get($this->cacheKey($panelId, 'active-users'), []);
        $activeUsers = is_array($activeUsers) ? $activeUsers : [];
        $activeUsers = array_filter(
            $activeUsers,
            static fn (mixed $lastSeen): bool => is_numeric($lastSeen) && ((float) $lastSeen >= $expiresBefore),
        );

        if ($user !== null) {
            $activeUsers[$this->userKey($user)] = $capturedAt;
        }

        $this->store()->put(
            $this->cacheKey($panelId, 'active-users'),
            $activeUsers,
            now()->addSeconds($this->heartbeatTtlSeconds() * 2),
        );

        $requests = $this->store()->get($this->cacheKey($panelId, 'request-samples'), []);
        $requests = is_array($requests) ? $requests : [];
        $requests = array_values(array_filter(
            $requests,
            fn (mixed $timestamp): bool => is_numeric($timestamp) && ((float) $timestamp >= ($capturedAt - $this->requestWindowSeconds())),
        ));

        if ($appendRequest) {
            $requests[] = $capturedAt;
        }

        $this->store()->put(
            $this->cacheKey($panelId, 'request-samples'),
            $requests,
            now()->addSeconds($this->requestWindowSeconds() * 6),
        );

        return [$activeUsers, $requests, $capturedAt];
    }

    protected function markBroadcastIfDue(string $panelId, float $capturedAt): bool
    {
        $key = $this->cacheKey($panelId, 'last-broadcast-at');
        $lastBroadcastAt = (float) $this->store()->get($key, 0.0);

        if (($capturedAt - $lastBroadcastAt) < $this->broadcastIntervalSeconds()) {
            return false;
        }

        $this->store()->put($key, $capturedAt, now()->addMinutes(10));

        return true;
    }

    protected function resolvePanelId(Panel | string | null $panel = null): ?string
    {
        return $this->resolvePanel($panel)?->getId();
    }

    protected function resolvePanel(Panel | string | null $panel = null): ?Panel
    {
        if ($panel instanceof Panel) {
            return $panel;
        }

        if (is_string($panel)) {
            return Filament::getPanel($panel, false);
        }

        return Filament::getCurrentPanel() ?? Filament::getDefaultPanel();
    }

    protected function resolvePlugin(Panel $panel): ?RealTimeStatsPanelPlugin
    {
        if (! $panel->hasPlugin(RealTimeStatsPanelPlugin::ID)) {
            return null;
        }

        $plugin = $panel->getPlugin(RealTimeStatsPanelPlugin::ID);

        return $plugin instanceof RealTimeStatsPanelPlugin ? $plugin : null;
    }

    protected function store(): Repository
    {
        $store = config('realtime-stats-panel.cache_store');

        return filled($store) ? $this->cache->store($store) : $this->cache->store();
    }

    protected function cacheKey(string $panelId, string $suffix): string
    {
        $prefix = trim((string) config('realtime-stats-panel.cache_prefix', 'realtime-stats-panel'), ':');

        return "{$prefix}:{$panelId}:{$suffix}";
    }

    protected function userKey(Authenticatable $user): string
    {
        return sprintf('%s:%s', $user::class, (string) $user->getAuthIdentifier());
    }

    protected function requestWindowSeconds(): int
    {
        return max(1, (int) config('realtime-stats-panel.request_window_seconds', 10));
    }

    protected function heartbeatTtlSeconds(): int
    {
        return max(5, (int) config('realtime-stats-panel.heartbeat_ttl_seconds', 90));
    }

    protected function historyPoints(): int
    {
        return max(5, (int) config('realtime-stats-panel.history_points', 20));
    }

    protected function broadcastIntervalSeconds(): int
    {
        return max(1, (int) config('realtime-stats-panel.broadcast_interval_seconds', 1));
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    protected function withLock(string $panelId, callable $callback): mixed
    {
        $store = $this->store();
        $cacheStore = $store->getStore();

        if ($cacheStore instanceof LockProvider) {
            return $store->lock($this->cacheKey($panelId, 'lock'), 3)->block(1, $callback);
        }

        return $callback();
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptySnapshot(): array
    {
        return [
            'panel_id' => null,
            'generated_at' => now()->toIso8601String(),
            'metrics' => [],
            'meta' => [],
        ];
    }
}
