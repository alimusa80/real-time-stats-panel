@php
    $attributes = new \Illuminate\View\ComponentAttributeBag;
@endphp

<x-filament-widgets::widget
    :attributes="
        $attributes->merge([
            'wire:poll.' . $pollingInterval => filled($pollingInterval) ? 'refreshSnapshot' : null,
        ], escape: false)
    "
>
    <x-filament::section
        heading="Real-time stats"
        description="WebSocket-fed operational telemetry for the active Filament panel."
    >
        <div
            x-data="realTimeStatsPanel({
                broadcastChannel: @js($broadcastChannel),
                broadcastEvent: @js($broadcastEvent),
                debounceMs: @js($debounceMs),
                echoWaitTimeoutMs: @js($echoWaitTimeoutMs),
                snapshot: @js($snapshot),
            })"
            x-init="init()"
            class="space-y-4"
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">
                    Metrics are sampled on authenticated panel traffic and pushed over a private Echo channel.
                </p>

                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset"
                        :class="connectionBadgeClasses()"
                    >
                        <span class="h-2 w-2 rounded-full" :class="connectionDotClasses()"></span>
                        <span x-text="connectionLabel()"></span>
                    </span>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5"
                        x-on:click="refreshFromServer()"
                    >
                        Refresh
                    </button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <template x-for="metric in metricCards()" :key="metric.key">
                    <article class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400" x-text="metric.label"></p>
                                <div class="flex items-end gap-2">
                                    <p class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white" x-text="metric.formatted_value"></p>
                                    <p class="pb-1 text-sm font-medium text-gray-500 dark:text-gray-400" x-show="metric.suffix" x-text="metric.suffix"></p>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="metric.description"></p>
                            </div>

                            <span
                                class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-medium ring-1 ring-inset"
                                :class="metricBadgeClasses(metric.color)"
                                x-text="metric.trend"
                            ></span>
                        </div>

                        <div class="mt-4 rounded-xl bg-gray-50 px-3 py-2 dark:bg-black/20">
                            <svg viewBox="0 0 100 32" preserveAspectRatio="none" class="h-10 w-full">
                                <polyline
                                    fill="none"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    :class="metricStrokeClasses(metric.color)"
                                    :points="sparklinePoints(metric.history)"
                                ></polyline>
                            </svg>
                        </div>
                    </article>
                </template>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
