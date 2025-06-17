import { defineConfig, loadEnv } from 'vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import ui from '@nuxt/ui/vite';

process.env = { ...process.env, ...loadEnv('', process.cwd()) };

export default defineConfig({
    server: {
        host: true /* Expose to all IP */,
        hmr: {
            host:
                process.env.VITE_PUSHER_HOST ??
                'docker.localhost' /* Set base URL for Hot Module Reload */,
        },
        cors: {
            origin: process.env.VITE_APP_URL ?? 'http://docker.localhost',
        },
    },
    plugins: [
        laravel({
            input: ['resources/ts/app.ts', 'resources/css/app.css'],
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        ui(),
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
    build: {
        rollupOptions: {
            output: {
                compact: true,
                manualChunks: {
                    vendor: ['vue', 'vue-router', 'axios', 'pinia', 'pusher-js'],
                },
            },
            external: ['fsevents'],
        },
        manifest: 'manifest.json',
    },
    test: {
        environment: 'jsdom',
    },
});
