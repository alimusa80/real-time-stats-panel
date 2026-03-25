/*
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

document.addEventListener('alpine:init', () => {
    window.Alpine.data('realTimeStatsPanel', (config = {}) => ({
        state: 'connecting',
        snapshot: config.snapshot ?? { metrics: {} },
        pendingSnapshot: null,
        flushTimer: null,
        echoLoadedListener: null,
        boundConnection: null,

        init() {
            this.connect();
        },

        connect() {
            if (window.Echo) {
                this.subscribe();
                return;
            }

            this.state = 'connecting';

            this.echoLoadedListener = () => {
                this.echoLoadedListener = null;
                this.subscribe();
            };

            window.addEventListener('EchoLoaded', this.echoLoadedListener, { once: true });

            window.setTimeout(() => {
                if (!window.Echo) {
                    this.state = 'disconnected';
                }
            }, Number(config.echoWaitTimeoutMs ?? 4000));
        },

        subscribe() {
            if (!window.Echo) {
                this.state = 'disconnected';
                return;
            }

            window.Echo.leave(config.broadcastChannel);
            this.state = 'connected';

            window.Echo.private(config.broadcastChannel).listen(
                `.${config.broadcastEvent}`,
                (payload) => {
                    this.queueSnapshot(payload.snapshot ?? payload);
                },
            );

            this.bindConnectionState();
        },

        bindConnectionState() {
            const connection = window.Echo?.connector?.pusher?.connection;

            if (!connection || this.boundConnection) {
                return;
            }

            this.boundConnection = connection;

            connection.bind('state_change', ({ current }) => {
                if (current === 'connected') {
                    this.state = 'connected';
                    return;
                }

                if (current === 'connecting' || current === 'unavailable') {
                    this.state = 'reconnecting';
                    return;
                }

                this.state = 'disconnected';
            });

            connection.bind('error', () => {
                this.state = 'disconnected';
            });
        },

        queueSnapshot(snapshot) {
            this.pendingSnapshot = snapshot;

            if (this.flushTimer) {
                return;
            }

            this.flushTimer = window.setTimeout(() => {
                this.flushSnapshot();
            }, Number(config.debounceMs ?? 350));
        },

        flushSnapshot() {
            if (this.pendingSnapshot) {
                this.snapshot = this.pendingSnapshot;
                this.pendingSnapshot = null;
            }

            if (this.flushTimer) {
                window.clearTimeout(this.flushTimer);
                this.flushTimer = null;
            }
        },

        refreshFromServer() {
            this.state = 'connecting';

            if (this.$wire?.refreshSnapshot) {
                this.$wire.refreshSnapshot().then(() => {
                    this.state = 'connected';
                }).catch(() => {
                    this.state = 'disconnected';
                });

                return;
            }

            this.connect();
        },

        metricCards() {
            return Object.entries(this.snapshot?.metrics ?? {}).map(([key, metric]) => ({
                key,
                ...metric,
            }));
        },

        sparklinePoints(history) {
            if (!Array.isArray(history) || history.length === 0) {
                return '0,16 100,16';
            }

            const values = history.map((value) => Number(value) || 0);
            const min = Math.min(...values);
            const max = Math.max(...values);
            const range = max - min || 1;

            return values.map((value, index) => {
                const x = values.length === 1 ? 50 : (index / (values.length - 1)) * 100;
                const y = 28 - (((value - min) / range) * 24);
                return `${x},${y}`;
            }).join(' ');
        },

        connectionLabel() {
            if (this.state === 'connected') {
                return 'Live';
            }

            if (this.state === 'reconnecting') {
                return 'Reconnecting';
            }

            if (this.state === 'connecting') {
                return 'Connecting';
            }

            return 'Disconnected';
        },

        connectionBadgeClasses() {
            if (this.state === 'connected') {
                return 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-500/10 dark:text-success-300';
            }

            if (this.state === 'reconnecting' || this.state === 'connecting') {
                return 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-500/10 dark:text-warning-300';
            }

            return 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-500/10 dark:text-danger-300';
        },

        connectionDotClasses() {
            if (this.state === 'connected') {
                return 'bg-success-500';
            }

            if (this.state === 'reconnecting' || this.state === 'connecting') {
                return 'bg-warning-500';
            }

            return 'bg-danger-500';
        },

        metricBadgeClasses(color) {
            const map = {
                danger: 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-500/10 dark:text-danger-300',
                warning: 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-500/10 dark:text-warning-300',
                success: 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-500/10 dark:text-success-300',
                primary: 'bg-primary-50 text-primary-700 ring-primary-600/20 dark:bg-primary-500/10 dark:text-primary-300',
                info: 'bg-info-50 text-info-700 ring-info-600/20 dark:bg-info-500/10 dark:text-info-300',
                gray: 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-white/10 dark:text-gray-300',
            };

            return map[color] ?? map.gray;
        },

        metricStrokeClasses(color) {
            const map = {
                danger: 'stroke-danger-500',
                warning: 'stroke-warning-500',
                success: 'stroke-success-500',
                primary: 'stroke-primary-500',
                info: 'stroke-info-500',
                gray: 'stroke-gray-400 dark:stroke-gray-500',
            };

            return map[color] ?? map.gray;
        },

        destroy() {
            if (window.Echo) {
                window.Echo.leave(config.broadcastChannel);
            }

            if (this.echoLoadedListener) {
                window.removeEventListener('EchoLoaded', this.echoLoadedListener);
                this.echoLoadedListener = null;
            }

            if (this.flushTimer) {
                window.clearTimeout(this.flushTimer);
                this.flushTimer = null;
            }
        },
    }));
});
