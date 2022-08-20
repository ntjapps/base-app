import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
      host: true, /* Expose to all IP */
      hmr: {
        host: 'kaji.docker.local', /* Set base URL for Hot Module Reload */
      },
    },
    plugins: [
      laravel({
        input: ['resources/ts/app.ts'],
        refresh: true,
      }),
      vue({
        template: {
          transformAssetUrls: {
            base: null,
            includeAbsolute: false,
          },
        },
      }),
    ],
    build: {
      rollupOptions: {
        output: {
          compact: true,
          manualChunks: {
            vendor: [
              'lodash',
              'axios',
              'sweetalert2',
              'vue',
              'vue-router',
              'pinia',
            ],
          },
        }
      }
    },
});