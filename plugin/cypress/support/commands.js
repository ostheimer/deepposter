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
 * Optimiert für schnellere Ausführung und bessere Fehlerbehandlung
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

/**
 * Custom-Befehl zum Einloggen in WordPress
 */
Cypress.Commands.add('loginToWordPress', () => {
  cy.visit('/wp-login.php');
  cy.get('#user_login').type('admin');
  cy.get('#user_pass').type('password');
  cy.get('#wp-submit').click();
  cy.url().should('include', '/wp-admin/');
});

// Custom command for WordPress login with improved performance
Cypress.Commands.add('login', () => {
    const username = 'deepposter';
    const password = 'deepposter';
    
    cy.log('Starting login process');
    
    // Besuche die Login-Seite direkt
    cy.visit('/wp-login.php', {
        timeout: 30000,
        retryOnNetworkFailure: true,
        onBeforeLoad(win) {
            // Verhindern, dass Bilder geladen werden, um die Ladezeit zu verbessern
            const originalOpen = win.XMLHttpRequest.prototype.open;
            win.XMLHttpRequest.prototype.open = function() {
                if (arguments[1] && arguments[1].includes('.jpg')) {
                    arguments[1] = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
                }
                return originalOpen.apply(this, arguments);
            };
        }
    });
    
    // Logging der aktuellen URL für die Fehlersuche
    cy.url().then(url => {
        cy.log('Current URL:', url);
    });
    
    // Warten auf das Login-Formular und sicherstellen, dass es vollständig geladen ist
    cy.get('#loginform', { timeout: 15000 })
        .should('be.visible')
        .within(() => {
            // Direkter Zugriff auf Formularelemente in einem Block für bessere Performance
            cy.get('#user_login')
                .should('be.visible')
                .clear()
                .type(username, { delay: 0 }); // Kein Verzögerung für maximale Geschwindigkeit
            
            cy.get('#user_pass')
                .should('be.visible')
                .clear()
                .type(password, { delay: 0 });
            
            // Submit direkt im Formular, verhindert Event-Bubbling
            cy.get('#wp-submit').click();
        });
    
    // Warten auf Weiterleitung mit erhöhtem Timeout
    cy.url().should('include', '/wp-admin/', { timeout: 20000 });
    
    // Überprüfen, ob der Admin-Bereich korrekt geladen wurde
    cy.get('body.wp-admin', { timeout: 20000 }).should('exist').then(() => {
        cy.log('Successfully logged in');
    });
});

/**
 * Visit the DeepPoster admin page with enhanced error handling and performance optimizations
 * @param {string} suffix - Optional suffix for the page, e.g. '-settings'
 */
Cypress.Commands.add('visitDeepPoster', (suffix = '') => {
    // Direkt einloggen statt zu prüfen
    cy.login();
    
    // Dann zur Plugin-Seite navigieren mit Performance-Optimierungen
    cy.log(`Navigating to DeepPoster${suffix} page`);
    cy.visit(`/wp-admin/admin.php?page=deepposter${suffix}`, {
        timeout: 30000,
        retryOnNetworkFailure: true,
        onBeforeLoad(win) {
            // Performance-Verbesserungen für schnelleres Laden
            // Verhindern, dass unnötige Assets geladen werden
            const originalFetch = win.fetch;
            win.fetch = function() {
                const url = arguments[0];
                if (typeof url === 'string' && (
                    url.includes('.svg') ||
                    url.includes('.png') ||
                    url.includes('.jpg') ||
                    url.includes('heartbeat') ||
                    url.includes('analytics')
                )) {
                    return Promise.resolve(new Response('', { status: 200 }));
                }
                return originalFetch.apply(this, arguments);
            };
        }
    });
    
    // Verbesserte Fehlerbehandlung mit retry-Logik
    cy.url().should('include', `page=deepposter${suffix}`).then(
        () => {
            cy.get('body.wp-admin').should('exist');
            cy.log(`Successfully navigated to DeepPoster${suffix} page`);
        },
        (error) => {
            cy.log('Navigation failed, retrying...', error);
            // Einmal erneut versuchen, falls die Navigation fehlgeschlagen ist
            cy.visit(`/wp-admin/admin.php?page=deepposter${suffix}`, {
                timeout: 30000,
                retryOnNetworkFailure: true
            });
            cy.url().should('include', `page=deepposter${suffix}`);
            cy.get('body.wp-admin').should('exist');
        }
    );
});

// Neue Hilfsfunktion zum effizienteren Warten auf AJAX-Anfragen
Cypress.Commands.add('waitForAjaxRequest', (actionName, alias = null, timeout = 10000) => {
    const requestAlias = alias || `${actionName}Request`;
    
    cy.intercept('POST', '**/admin-ajax.php', (req) => {
        if (req.body && req.body.includes(`action=${actionName}`)) {
            req.alias = requestAlias;
        }
    });
    
    return cy.wait(`@${requestAlias}`, { timeout });
});

// Neue Hilfsfunktion zum Auswählen einer Kategorie nach Namen
Cypress.Commands.add('selectCategory', (categoryName) => {
    if (categoryName) {
        cy.get('#categorySelect option').each(($option) => {
            if ($option.text().includes(categoryName)) {
                cy.get('#categorySelect').select($option.val());
                return false; // Schleife abbrechen
            }
        });
    } else {
        // Erste Kategorie auswählen, wenn kein Name angegeben wurde
        cy.get('#categorySelect option').first().then(($option) => {
            cy.get('#categorySelect').select($option.val());
        });
    }
    
    // Überprüfen, ob eine Kategorie ausgewählt wurde
    cy.get('#categorySelect').should('not.have.value', '');
});

// Neue Hilfsfunktion zum Erstellen und Speichern eines Prompts
Cypress.Commands.add('createPrompt', (promptText) => {
    // Prompt in das Textfeld eingeben
    cy.get('#promptText').clear().type(promptText);
    
    // AJAX-Anfrage für das Speichern des Prompts abfangen
    cy.waitForAjaxRequest('deepposter_save_prompt', 'savePromptRequest');
    
    // Prompt speichern
    cy.get('#savePrompt').click();
    
    // Warten auf AJAX-Anfrage und Erfolgsmeldung überprüfen
    cy.wait('@savePromptRequest', { timeout: 10000 });
    cy.get('.notice-success').should('be.visible')
        .and('contain', 'Prompt erfolgreich gespeichert');
    
    // AJAX-Anfrage für das Neuladen der Prompts abfangen
    cy.waitForAjaxRequest('deepposter_get_prompts', 'reloadPromptsRequest');
    
    // Warten auf AJAX-Anfrage und überprüfen, ob der Prompt im Dropdown erscheint
    cy.wait('@reloadPromptsRequest', { timeout: 10000 });
    cy.get('#promptSelect').should('contain', promptText);
});

// Aktualisierte Funktion zum Auffangen von Konsolenausgaben mit besserer Speichernutzung
const logs = [];
const maxLogs = 500; // Reduziert für bessere Leistung

Cypress.on('window:before:load', (win) => {
    // Speichere originale Konsolenfunktionen
    const origLog = win.console.log;
    const origError = win.console.error;
    const origWarn = win.console.warn;

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
        // Fehler immer protokollieren, auch wenn das Maximum erreicht ist
        logs.push({
            type: 'error',
            timestamp: new Date().toISOString(),
            message: args.map(arg => 
                typeof arg === 'object' ? JSON.stringify(arg) : String(arg)
            ).join(' ')
        });
        
        // Bei Erreichen des Limits älteste Logs entfernen
        if (logs.length > maxLogs) {
            logs.shift();
        }
        
        origError.apply(win.console, args);
    };
    
    // Überschreibe console.warn
    win.console.warn = (...args) => {
        if (logs.length < maxLogs) {
            logs.push({
                type: 'warn',
                timestamp: new Date().toISOString(),
                message: args.map(arg => 
                    typeof arg === 'object' ? JSON.stringify(arg) : String(arg)
                ).join(' ')
            });
        }
        origWarn.apply(win.console, args);
    };
});

// Befehl zum Abrufen der Konsolenausgaben
Cypress.Commands.add('getConsoleLogs', (type = null) => {
    if (type) {
        return cy.wrap(logs.filter(log => log.type === type));
    }
    return cy.wrap(logs);
});

// Befehl zum Löschen der Konsolenausgaben
Cypress.Commands.add('clearConsoleLogs', () => {
    logs.length = 0;
    return cy.wrap(null);
});

// Befehl zum Debuggen der Seitenstruktur mit verbesserten Details
Cypress.Commands.add('debugPage', () => {
    cy.log('Debugging page structure');
    
    cy.document().then((doc) => {
        // Log basic page info
        cy.log('Page Title:', doc.title);
        cy.log('Body Classes:', doc.body.className);
        
        // Log all forms on the page with more details
        const forms = doc.querySelectorAll('form');
        cy.log(`Found ${forms.length} forms on the page:`);
        forms.forEach((form, index) => {
            const formInputs = [];
            form.querySelectorAll('input, select, textarea').forEach(input => {
                formInputs.push({
                    type: input.nodeName.toLowerCase(),
                    id: input.id,
                    name: input.name,
                    value: input.value
                });
            });
            
            cy.log(`Form ${index + 1}:`, {
                id: form.id,
                className: form.className,
                action: form.action,
                method: form.method,
                inputs: formInputs
            });
        });
        
        // Log specific elements we're looking for with more details
        const elements = {
            deepposterSettingsForm: doc.querySelector('#deepposterSettingsForm'),
            promptText: doc.querySelector('#promptText'),
            promptSelect: doc.querySelector('#promptSelect'),
            categorySelect: doc.querySelector('#categorySelect'),
            savePrompt: doc.querySelector('#savePrompt'),
            generateButton: doc.querySelector('#generateButton'),
            generationResults: doc.querySelector('#generationResults')
        };
        
        cy.log('Target Elements Found:', Object.entries(elements).reduce((acc, [key, el]) => {
            acc[key] = el ? {
                exists: true,
                visible: el.offsetParent !== null,
                id: el.id,
                className: el.className,
                tagName: el.tagName,
                value: el.value,
                textContent: el.textContent ? (el.textContent.length > 100 ? el.textContent.substring(0, 100) + '...' : el.textContent) : null
            } : {
                exists: false
            };
            return acc;
        }, {}));
    });
}); 