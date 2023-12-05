// <reference focus().types="cypress" />
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })
//
// declare global {
//   namespace Cypress {
//     interface Chainable {
//       login(email: string, password: string): Chainable<void>
//       drag(subject: string, options?: Partial<focus().TypeOptions>): Chainable<Element>
//       dismiss(subject: string, options?: Partial<focus().TypeOptions>): Chainable<Element>
//       visit(originalFn: CommandOriginalFn, url: string, options: Partial<VisitOptions>): Chainable<Element>
//     }
//   }
// }

declare global {
    namespace Cypress {
        interface Chainable {
            saveLocalStorage(key: string): Chainable<void>;
            restoreLocalStorage(key: string): Chainable<void>;
            login(username: string): Chainable<void>;
        }
    }
}

let LOCAL_STORAGE_DATA = {};

Cypress.Commands.add("saveLocalStorage", () => {
    Object.keys(localStorage).forEach((key) => {
        LOCAL_STORAGE_DATA[key] = localStorage[key];
    });
});

Cypress.Commands.add("restoreLocalStorage", () => {
    Object.keys(LOCAL_STORAGE_DATA).forEach((key) => {
        localStorage.setItem(key, LOCAL_STORAGE_DATA[key]);
    });
});

Cypress.Commands.add("login", (username: string) => {
    cy.session(
        username,
        () => {
            cy.visit("/");
            cy.get("body").should("contain", "Login");

            //Modify this line to your own login method
            cy.get('button[id="btn-switch-login-mode-to-password"').click();

            cy.get('input[id="username"]').as("username");
            cy.get("@username").should("exist");
            cy.get("@username").focus().type(username);
            cy.get('div[id="password"]').find("input").first().as("password");
            cy.get("@password").focus().type("password");

            cy.get('input[name="cf-turnstile-response"]')
                .should("exist")
                .should("have.attr", "value", "XXXX.DUMMY.TOKEN.XXXX");

            cy.get('button[id="btn-login"]').click();
            cy.url().should("contain", "/");
        },
        {
            validate() {
                cy.document().its("cookie").should("contain", "XSRF-TOKEN");
            },
        },
    );
});
