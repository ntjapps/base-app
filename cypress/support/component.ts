// Import commands.js using ES2015 syntax:
import './commands'

// Alternatively you can use CommonJS syntax:
// require('./commands')

import { mount } from 'cypress/vue'
// Ensure global styles are loaded
import '../../resources/css/app.scss'

Cypress.Commands.add('mount', mount)

// Example use:
// cy.mount(MyComponent)