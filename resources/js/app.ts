import { createInertiaApp } from '@inertiajs/vue3';
import { configureEcho } from '@laravel/echo-vue';
import { createPinia } from 'pinia';
import type { DefineComponent } from 'vue';

if (!import.meta.env.SSR) {
    configureEcho({
        broadcaster: 'reverb',
    });
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const corePages = import.meta.glob<DefineComponent>('./pages/**/*.vue');
const packagePages = import.meta.glob<DefineComponent>(
    '../../packages/*/resources/js/**/*.vue',
);

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: '#4B5563',
    },
    resolve: (name) => {
        const core = corePages[`./pages/${name}.vue`];

        if (core) {
            return core();
        }

        const suffix = `/resources/js/${name}.vue`;
        const match = Object.keys(packagePages).find((key) => key.endsWith(suffix));

        if (match && packagePages[match]) {
            return packagePages[match]();
        }

        throw new Error(`Inertia page not found: ${name}`);
    },
    withApp(app) {
        app.use(createPinia());
    },
});
