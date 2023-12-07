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

    it('Try to load protected webpage, should be redirected to login', () => {
        cy.visit('/dashboard')
        cy.get('head').should('exist')
        cy.get('head meta[name=cypress]').should('exist').should('have.attr', 'content', 'Cypress Testing')

        cy.get('body').should('exist')
        cy.get('body').should('contain', 'Login')
    })

    it('Try to login', () => {
        cy.formLogin('admin')
        cy.visit('/dashboard')
        cy.get('head').should('exist')
        cy.get('head meta[name=cypress]').should('exist').should('have.attr', 'content', 'Cypress Testing')

        cy.get('body').should('exist')
        cy.get('body').should('contain', 'Dashboard')
    })

    it('Try to logout', () => {
        cy.formLogin('admin')
        cy.visit('/dashboard')

        cy.get('body').should('exist')
        cy.get('body').should('contain', 'Dashboard')

        cy.request('/get-logout').then((response) => {
            expect(response.status).to.gte(200).and.to.lte(399)
        })

        cy.visit('/dashboard')
        cy.get('head').should('exist')
        cy.get('head meta[name=cypress]').should('exist').should('have.attr', 'content', 'Cypress Testing')

        cy.get('body').should('exist')
        cy.get('body').should('contain', 'Login')
    })
})