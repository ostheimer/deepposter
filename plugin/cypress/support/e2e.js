// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************

Cypress.Commands.add('login', () => {
    cy.visit('/wp-login.php', {
        timeout: 10000,
        retryOnNetworkFailure: true,
        retryOnStatusCodeFailure: true
    });

    // Warte bis die Login-Seite geladen ist
    cy.get('#loginform').should('be.visible');

    // Login-Daten eingeben
    cy.get('#user_login').clear().type(Cypress.env('WP_USER') || 'admin');
    cy.get('#user_pass').clear().type(Cypress.env('WP_PASS') || 'admin');
    
    // Login-Button klicken
    cy.get('#wp-submit').click();
    
    // Warte auf erfolgreichen Login
    cy.url().should('include', '/wp-admin', { timeout: 10000 });
});

// Füge Screenshot-Befehle hinzu
Cypress.Commands.add('logScreenshot', (name) => {
    cy.screenshot(name, {
        capture: 'viewport',
        overwrite: true
    });
}); 