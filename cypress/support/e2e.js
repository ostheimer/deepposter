// Import commands.js using ES2015 syntax:
import './commands'

// Hide fetch/XHR requests from command log
const app = window.top;
if (app && !app.document.head.querySelector('[data-hide-command-log-request]')) {
  const style = app.document.createElement('style');
  style.innerHTML =
    '.command-name-request, .command-name-xhr { display: none }';
  style.setAttribute('data-hide-command-log-request', '');
  app.document.head.appendChild(style);
}

Cypress.Commands.add('login', () => {
  const username = Cypress.env('wpUsername');
  const password = Cypress.env('wpPassword');
  
  cy.log(`Login-Versuch mit Benutzername: ${username}`);
  
  // Besuche die Login-Seite
  cy.visit('/wp-login.php', {
    timeout: 30000,
    retryOnStatusCodeFailure: true,
    retryOnNetworkFailure: true
  }).then(() => {
    cy.log('Login-Seite geladen');
  });
  
  // Warte 1 Sekunde nach dem Laden der Seite
  cy.wait(1000).then(() => {
    cy.log('1 Sekunde gewartet nach Laden der Login-Seite');
  });
  
  // Warte auf das Login-Formular
  cy.get('#loginform').should('be.visible').within(() => {
    // Username eingeben
    cy.get('#user_login')
      .should('be.visible')
      .clear()
      .type(username, { delay: 50 })
      .should('have.value', username)
      .then(() => {
        cy.log('Benutzername eingegeben');
      });
    
    // Passwort eingeben
    cy.get('#user_pass')
      .should('be.visible')
      .clear()
      .type(password, { delay: 50 })
      .should('have.value', password)
      .then(() => {
        cy.log('Passwort eingegeben');
      });
    
    // Submit-Button klicken
    cy.get('#wp-submit')
      .should('be.visible')
      .click()
      .then(() => {
        cy.log('Login-Button geklickt');
      });
  });
  
  // Warte auf erfolgreiche Weiterleitung
  cy.url({ timeout: 30000 }).should('include', '/wp-admin').then((url) => {
    cy.log(`Weitergeleitet zu: ${url}`);
  });
  
  // Verifiziere erfolgreichen Login
  cy.get('#wpadminbar').should('exist').then(() => {
    cy.log('Login erfolgreich - Admin-Bar gefunden');
  });
  
  // Zusätzliche Verifizierung des Login-Status
  cy.window().then((win) => {
    const userElement = win.document.querySelector('#wp-admin-bar-my-account');
    if (userElement) {
      cy.log(`Eingeloggt als: ${userElement.textContent.trim()}`);
    }
  });
});