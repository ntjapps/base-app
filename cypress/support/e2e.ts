before(() => {
    cy.exec(
        "if [ -f .env.cypress.backup ]; then echo 'It seems Cypress did not finish last time. Check the .env file to prevent loss of data.'; cp .env.cypress.backup .env; rm .env.cypress.backup; fi"
    );
    cy.exec("echo 'Cypress started. Backing up .env file.'");
    cy.exec("cp .env .env.cypress.backup");
    cy.exec("cp .env.testing .env");

    cy.exec("echo 'Cypress started. Installing dependencies.'");
    cy.exec("npm run build");
});

after(() => {
    cy.exec("cp .env.cypress.backup .env");
    cy.exec("rm .env.cypress.backup");
    cy.exec("echo 'Cypress finished. Restored .env file.'");

    cy.exec("echo 'Cypress finished. Removing dependencies.'");
    cy.exec("rm -rf public/build");
});