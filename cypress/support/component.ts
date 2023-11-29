// Import commands.js using ES2015 syntax:
import './commands'

import { mount } from 'cypress/vue'
// Ensure global styles are loaded
import '../../resources/ts/app.js'
import '../../resources/css/app.scss'

Cypress.Commands.add('mount', (component, options) => {
    return mount(component, options)
});
