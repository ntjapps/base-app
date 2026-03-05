import { defineConfig } from 'vitest/config';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['./tests/setupVitest.ts'],
        threads: false,
        coverage: {
            provider: 'istanbul',
            all: false,
            include: ['resources/**/*.ts'],
            exclude: [
                '**/*.spec.ts',
                '**/*.test.ts',
                '**/dist/**',
                '**/node_modules/**',
                '**/vendor/**',
                '**/tests/**',
                'resources/ts/app.ts',
                'resources/ts/bootstrap.ts',
                'resources/vue/AppVue.ts',
            ],
            reporter: ['text', 'html'],
            thresholds: {
                lines: 70,
                statements: 70,
                functions: 70,
                branches: 50,
            },
        },
        exclude: ['**/dist/**', '**/node_modules/**', '**/vendor/**'],
        deps: {
            // inline some deps that need ESM/CommonJS interop inside test env
            inline: ['@nuxt/ui'],
        },
    },
    resolve: {
        alias: [
            { find: 'vue/server-renderer', replacement: '@vue/server-renderer' },
            { find: 'vue', replacement: 'vue/dist/vue.esm-bundler.js' },
            {
                find: /^reka-ui\/dist\/Tooltip\/TooltipRoot(\.js)?$/,
                replacement: resolve(
                    dirname(fileURLToPath(import.meta.url)),
                    'tests/mocks/reka-ui/TooltipRoot',
                ),
            },
            {
                find: /^reka-ui\/dist\/Tooltip\/Tooltip(\.js)?$/,
                replacement: resolve(
                    dirname(fileURLToPath(import.meta.url)),
                    'tests/mocks/reka-ui/Tooltip',
                ),
            },
            {
                find: /^reka-ui\/dist\/shared\/createContext(\.js)?$/,
                replacement: resolve(
                    dirname(fileURLToPath(import.meta.url)),
                    'tests/mocks/reka-ui/createContext',
                ),
            },
            {
                find: /^@nuxt\/ui\/dist\/runtime\/components\/.*\.vue$/,
                replacement: resolve(
                    dirname(fileURLToPath(import.meta.url)),
                    'tests/mocks/nuxt-ui/StubComponent',
                ),
            },
            {
                find: /^@nuxt\/ui\/dist\/runtime\/vue\/components\/.*\.vue$/,
                replacement: resolve(
                    dirname(fileURLToPath(import.meta.url)),
                    'tests/mocks/nuxt-ui/StubComponent',
                ),
            },
            {
                find: '@nuxt/ui/vue-plugin',
                replacement: resolve(
                    dirname(fileURLToPath(import.meta.url)),
                    'tests/mocks/nuxt-ui/vue-plugin',
                ),
            },
        ],
    },
});
