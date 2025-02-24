// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************

/**
 * Check if the user is already logged in, and if not, log in
 */
Cypress.Commands.add('loginIfNeeded', () => {
  cy.log('Checking if login is needed...');

  // Prüfe, ob der Admin-Bereich bereits zugänglich ist
  cy.get('body').then($body => {
    // Wenn das Login-Formular sichtbar ist, muss eingeloggt werden
    if ($body.find('#loginform').length > 0) {
      cy.log('Login form detected, logging in');
      cy.login();
    } else {
      cy.log('Already logged in');
    }
  });
});

// Custom command for WordPress login
Cypress.Commands.add('login', () => {
    const username = 'deepposter';
    const password = 'deepposter';
    
    cy.log('Starting login process');
    
    // Besuche die Login-Seite direkt
    cy.visit('/wp-login.php', {
        timeout: 30000,
        retryOnNetworkFailure: true
    });
    
    // Debug-Ausgabe der aktuellen URL
    cy.url().then(url => {
        cy.log('Current URL:', url);
    });
    
    // Warte auf das Login-Formular und stelle sicher, dass es vollständig geladen ist
    cy.get('#loginform', { timeout: 15000 }).should('be.visible');
    
    // Zusätzliche Wartezeit für die vollständige Seitenladung (reduziert für schnellere Tests)
    cy.wait(300);
    
    cy.log('Login form is ready - starting input');
    
    // Anmeldedaten eingeben mit kürzerer Verzögerung für schnellere Tests
    cy.get('#user_login')
      .should('be.visible')
      .focus()
      .clear()
      .should('be.empty')
      .type(username, { delay: 50, force: true });
    
    cy.get('#user_pass')
      .should('be.visible')
      .focus()
      .clear()
      .type(password, { delay: 50, force: true });
    
    // Debug-Ausgabe vor dem Klick
    cy.log('Submitting login form');
    
    // Submit-Button klicken
    cy.get('#wp-submit').should('be.visible').click();
    
    // Warte auf Weiterleitung
    cy.url().should('include', '/wp-admin/', { timeout: 20000 });
    
    // Überprüfe Admin-Bereich
    cy.get('body.wp-admin', { timeout: 20000 }).should('exist').then(() => {
        cy.log('Successfully logged in');
    });
    
    // Debug-Ausgabe der Cookies
    cy.getCookies().then(cookies => {
        cy.log('Current cookies:', cookies);
    });
});

/**
 * Visit the DeepPoster admin page
 * @param {string} suffix - Optional suffix for the page, e.g. '-settings'
 */
Cypress.Commands.add('visitDeepPoster', (suffix = '') => {
  // Zuerst einloggen
  cy.login();
  
  // Dann zur Plugin-Seite navigieren
  cy.log(`Navigating to DeepPoster${suffix} page`);
  cy.visit(`/wp-admin/admin.php?page=deepposter${suffix}`, {
    timeout: 30000,
    retryOnNetworkFailure: true
  });
  
  // Überprüfen, ob die Seite korrekt geladen wurde
  cy.url().should('include', `page=deepposter${suffix}`);
  cy.get('body.wp-admin').should('exist');
  cy.log(`Successfully navigated to DeepPoster${suffix} page`);
});

// Erfasse Konsolenausgaben
const logs = [];
const maxLogs = 1000;

Cypress.on('window:before:load', (win) => {
    // Speichere originale Konsolenfunktionen
    const origLog = win.console.log;
    const origError = win.console.error;

    // Überschreibe console.log
    win.console.log = (...args) => {
        if (logs.length < maxLogs) {
            logs.push({
                type: 'log',
                timestamp: new Date().toISOString(),
                message: args.map(arg => 
                    typeof arg === 'object' ? JSON.stringify(arg) : String(arg)
                ).join(' ')
            });
        }
        origLog.apply(win.console, args);
    };

    // Überschreibe console.error
    win.console.error = (...args) => {
        if (logs.length < maxLogs) {
            logs.push({
                type: 'error',
                timestamp: new Date().toISOString(),
                message: args.map(arg => 
                    typeof arg === 'object' ? JSON.stringify(arg) : String(arg)
                ).join(' ')
            });
        }
        origError.apply(win.console, args);
    };
});

// Befehl zum Abrufen der Konsolenausgaben
Cypress.Commands.add('getConsoleLogs', () => {
    return cy.wrap(logs);
});

// Befehl zum Löschen der Konsolenausgaben
Cypress.Commands.add('clearConsoleLogs', () => {
    logs.length = 0;
    return cy.wrap(null);
});

// Befehl zum Debuggen der Seitenstruktur
Cypress.Commands.add('debugPage', () => {
    cy.log('Debugging page structure');
    
    cy.document().then((doc) => {
        // Log basic page info
        cy.log('Page Title:', doc.title);
        cy.log('Body Classes:', doc.body.className);
        
        // Log all forms on the page
        const forms = doc.querySelectorAll('form');
        cy.log(`Found ${forms.length} forms on the page:`);
        forms.forEach((form, index) => {
            cy.log(`Form ${index + 1}:`, {
                id: form.id,
                className: form.className,
                action: form.action,
                method: form.method
            });
        });
        
        // Log specific elements we're looking for
        const elements = {
            deepposterSettingsForm: doc.querySelector('#deepposterSettingsForm'),
            promptText: doc.querySelector('#promptText'),
            promptSelect: doc.querySelector('#promptSelect'),
            categorySelect: doc.querySelector('#categorySelect'),
            savePrompt: doc.querySelector('#savePrompt')
        };
        
        cy.log('Target Elements Found:', Object.entries(elements).reduce((acc, [key, el]) => {
            acc[key] = el ? {
                exists: true,
                visible: el.offsetParent !== null,
                id: el.id,
                className: el.className
            } : {
                exists: false
            };
            return acc;
        }, {}));
        
        // Log complete page structure
        cy.log('Complete Page Structure:', {
            html: doc.documentElement.outerHTML
        });
    });
}); 