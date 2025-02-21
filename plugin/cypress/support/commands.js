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
    const username = Cypress.env('wpUsername') || 'deepposter';
    const password = Cypress.env('wpPassword') || 'deepposter';
    
    cy.session([username, password], () => {
        cy.visit('/wp-admin/');
        
        // Warte auf das Login-Formular
        cy.get('#loginform', { timeout: 10000 }).should('be.visible');
        
        // Username eingeben
        cy.get('#user_login')
            .clear()
            .type(username, { delay: 50 })
            .should('have.value', username);
        
        // Passwort eingeben
        cy.get('#user_pass')
            .clear()
            .type(password, { delay: 50 })
            .should('have.value', password);
        
        // Submit-Button klicken
        cy.get('#wp-submit').click();
        
        // Warte auf erfolgreiche Weiterleitung
        cy.url({ timeout: 30000 }).should('include', '/wp-admin');
        
        // Warte auf Admin-Bar als Indikator für erfolgreichen Login
        cy.get('#wpadminbar', { timeout: 10000 }).should('exist');
    });
});

// Custom command für DeepPoster Navigation
Cypress.Commands.add('visitDeepPoster', (subpage = '') => {
    const page = subpage ? `deepposter${subpage}` : 'deepposter';
    cy.visit(`/wp-admin/admin.php?page=${page}`);
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
    cy.log('Debugging Seitenstruktur');
    
    cy.document().then((doc) => {
        // Logge den HTML-Inhalt des Body
        cy.log('Body HTML Struktur:');
        cy.log(doc.body.innerHTML);
        
        // Überprüfe spezifische Elemente
        const aiGenerator = doc.querySelector('#aiGeneratorForm');
        const promptText = doc.querySelector('textarea[name="prompt"]');
        const categorySelect = doc.querySelector('select[name="category"]');
        
        cy.log('Gefundene Elemente:');
        cy.log(`aiGeneratorForm: ${aiGenerator ? 'gefunden' : 'nicht gefunden'}`);
        cy.log(`promptText: ${promptText ? 'gefunden' : 'nicht gefunden'}`);
        cy.log(`categorySelect: ${categorySelect ? 'gefunden' : 'nicht gefunden'}`);
    });
}); 