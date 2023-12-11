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
        cy.request("/sanctum/csrf-cookie")
            .its("body")
            .then((body) => {
                const csrfToken = body.csrf_token;
                cy.request({
                    method: "POST",
                    url: "/post-login",
                    failOnStatusCode: false,
                    form: true,
                    body: {
                        username: username,
                        password: "password",
                        token: "XXXX.DUMMY.TOKEN.XXXX",
                    },
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                    },
                }).then((response) => {
                    cy.getCookie("laravel_session").should("exist");
                    expect(response.status).to.gte(200).and.to.lte(399);
                });
            });
    });
});
