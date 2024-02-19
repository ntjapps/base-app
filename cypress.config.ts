import { defineConfig } from "cypress";

export default defineConfig({
    watchForFileChanges: false,
    numTestsKeptInMemory: 20,
    downloadsFolder: "tests/cypress/output/downloads",
    fixturesFolder: "tests/cypress/fixtures",
    screenshotsFolder: "tests/cypress/output/screenshots",
    videosFolder: "tests/cypress/output/videos",
    video: true,
    e2e: {
        baseUrl: "http://docker.localhost",
        supportFile: "tests/cypress/support/e2e.{js,jsx,ts,tsx}",
        specPattern: "tests/cypress/e2e/**/*.cy.{js,jsx,ts,tsx}",
    },
});
