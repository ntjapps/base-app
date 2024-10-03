export const activatePreTesting = () => {
    cy.exec('cp .env .env.backup ; cp .env.testing .env');

    return null;
};

export const returnPostTesting = () => {
    cy.exec('cp .env.backup .env ; rm .env.backup');

    return null;
};
