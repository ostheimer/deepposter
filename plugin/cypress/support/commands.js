// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************

// Custom command for WordPress login
Cypress.Commands.add('login', () => {
    const username = Cypress.env('wpUsername');
    const password = Cypress.env('wpPassword');
    
    cy.session([username, password], () => {
        cy.visit('/wp-login.php');
        
        // Debug-Ausgabe der aktuellen URL
        cy.url().then(url => {
            cy.log('Login URL:', url);
        });
        
        // Warte auf das Login-Formular
        cy.get('#loginform').should('be.visible');
        
        // Anmeldedaten eingeben
        cy.get('#user_login').clear().type(username);
        cy.get('#user_pass').clear().type(password);
        cy.get('#wp-submit').click();
        
        // Warte auf erfolgreiche Weiterleitung
        cy.url().should('include', '/wp-admin/');
        
        // Warte auf Admin-Bar
        cy.get('body.wp-admin').should('exist');
        
        // Debug-Ausgabe der Cookies
        cy.getCookies().then(cookies => {
            cy.log('Cookies nach Login:', cookies);
        });
    });
});

// Custom command for DeepPoster navigation
Cypress.Commands.add('visitDeepPoster', (subpage = '') => {
    cy.login();
    
    // Construct the URL based on subpage
    let url;
    if (subpage === '-settings') {
        url = '/wp-admin/admin.php?page=deepposter-settings';
    } else {
        url = '/wp-admin/admin.php?page=deepposter';
    }
    
    cy.log('Navigating to:', url);
    cy.visit(url);
    
    // Debug-Ausgabe
    cy.url().then(currentUrl => {
        cy.log('Current URL:', currentUrl);
    });
    
    cy.getCookies().then(cookies => {
        cy.log('Current Cookies:', cookies);
    });
    
    // Wait for page to load
    cy.get('body.wp-admin').should('exist');
    
    // Debug page content
    cy.get('body').then($body => {
        cy.log('Body Content:', {
            html: $body.html(),
            text: $body.text()
        });
        
        // Log all headings
        const headings = $body.find('h1, h2, h3, h4, h5, h6');
        cy.log('Found Headings:', headings.length);
        headings.each((i, el) => {
            cy.log(`Heading ${i + 1}:`, {
                tag: el.tagName,
                text: el.textContent,
                classes: el.className
            });
        });
        
        // Log all forms
        const forms = $body.find('form');
        cy.log('Found Forms:', forms.length);
        forms.each((i, el) => {
            cy.log(`Form ${i + 1}:`, {
                id: el.id,
                action: el.action,
                method: el.method,
                classes: el.className
            });
        });
    });
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