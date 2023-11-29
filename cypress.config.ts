import { defineConfig } from 'cypress'
import vitePreprocessor from 'cypress-vite'

export default defineConfig({
    e2e: {
        baseUrl: 'http://base_ols',
        setupNodeEvents(on, config) {
            on('file:preprocessor', vitePreprocessor(config))
        },
        video: true,
        screenshotOnRunFailure: true,
    },
    component: {
        devServer: {
            framework: 'vue',
            bundler: 'vite',
        },
        video: true,
        screenshotOnRunFailure: true,
    },
})