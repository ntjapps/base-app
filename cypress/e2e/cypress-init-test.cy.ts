describe('My First Test / Cypress Init Test', () => {
    it('Does not do much! Just testing if Cypress works.', () => {
        expect(true).to.equal(true)
    })

    it('Check if Cypress can access the app', () => {
        cy.visit('/').then(() => {
            cy.get('head meta[name="cypress"]').should('have.attr', 'content', 'Cypress Testing')
        })
    })
})
