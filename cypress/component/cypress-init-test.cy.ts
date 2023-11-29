import CmpTesting from '../../resources/vue/Components/CmpTesting.vue'

describe('My First Test / Cypress Init Test', () => {
    it('Does not do much! Just testing if Cypress works.', () => {
        expect(true).to.equal(true)
    })

    it('Load CmpTesting.vue', () => {
        cy.mount(CmpTesting);
        cy.contains('Testing Cypress');
    })
})