describe('Initialization of Cypress', () => {
    it('Does not do much!', () => {
        expect(true).to.equal(true)
    })

    it('Try to load webpage, any webpage will do', () => {
        cy.visit('/')
        cy.get('head').should('exist')
        cy.get('head meta[name=cypress]').should('exist').should('have.attr', 'content', 'Cypress Testing')

        cy.get('body').should('exist')
    })
})