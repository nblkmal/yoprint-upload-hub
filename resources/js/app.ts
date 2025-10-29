import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';
import Echo from 'laravel-echo';
// import Pusher from 'pusher-js';
import { configureEcho } from '@laravel/echo-vue';

configureEcho({
    broadcaster: 'pusher',
});

// Configure Laravel Echo
// window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    wsHost: import.meta.env.VITE_PUSHER_HOST ?? window.location.hostname,
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? (window.location.protocol === 'https:' ? 443 : 80), // Use nginx proxy port
    wssPort: 443, // Use nginx proxy port for WSS
    forceTLS: window.location.protocol === 'https:', // Use TLS if HTTPS
    enabledTransports: ['ws', 'wss'],
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin);
        
        // Wait a bit to ensure Echo is fully initialized before mounting
        setTimeout(() => {
            app.mount(el);
        }, 100);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
