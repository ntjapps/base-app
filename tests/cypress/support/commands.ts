declare namespace Cypress {
    interface Chainable {
        /**
         * Custom command to select DOM element by label text.
         * @example cy.formLogin('email', 'password')
         */
        formLogin(username: string): Chainable<Element>;
    }
}

Cypress.Commands.add("formLogin", (username) => {
    cy.session("login-" + username, () => {
        cy.visit("/login-redirect");
        cy.get('[data-test="username"]').type(username);
        cy.get('[data-test="password"]').type("password");
        cy.get('[data-test="login"]').click();
        cy.url().should("include", "/dashboard");
    });
});
