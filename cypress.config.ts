import { defineConfig } from 'cypress'
import vitePreprocessor from 'cypress-vite'

export default defineConfig({
    watchForFileChanges: false,
    numTestsKeptInMemory: 20,
    downloadsFolder: 'tests/cypress/output/downloads',
    fixturesFolder: 'tests/cypress/fixtures',
    screenshotsFolder: 'tests/cypress/output/screenshots',
    videosFolder: 'tests/cypress/output/videos',
    video: true,
    e2e: {
        baseUrl: 'http://base_ols',
        setupNodeEvents(on) {
            on('file:preprocessor', vitePreprocessor({
                configFile: false,
            }))
        },
        supportFile: 'tests/cypress/support/e2e.{js,jsx,ts,tsx}',
        specPattern: 'tests/cypress/e2e/**/*.cy.{js,jsx,ts,tsx}',
    },
    component: {
        devServer: {
            framework: 'vue',
            bundler: 'vite',
        },
        indexHtmlFile: 'tests/cypress/component/index.html',
        supportFile: 'tests/cypress/support/component.{js,jsx,ts,tsx}',
        specPattern: 'tests/cypress/component/**/*.cy.{js,jsx,ts,tsx}',
    },
})