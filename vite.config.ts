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
        watch: {
            ignored: ['**/public/**', '**/public/build/**', '**/storage/**', '**/vendor/**'],
        },
    },
    cacheDir: 'node_modules/.vite',
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
                    'vue-core': ['vue', 'vue-router', 'pinia'],
                    network: ['axios', 'pusher-js'],
                },
            },
            external: [
                'fsevents',
                /\.node$/,
                /^node:/,
                '@nuxt/ui',
                '@nuxt/kit',
                '@tailwindcss/oxide',
                'lightningcss',
            ],
        },
        manifest: 'manifest.json',
    },
});
