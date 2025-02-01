// -- This is a parent command --
Cypress.Commands.add('login', () => {
  const username = Cypress.env('wpUsername') || 'admin'
  const password = Cypress.env('wpPassword') || 'admin'
  
  cy.session([username, password], () => {
    cy.visit('/wp-admin/')
    cy.get('#user_login').clear().type(username)
    cy.get('#user_pass').clear().type(password)
    cy.get('#wp-submit').click()
  })
})

// Custom command für DeepPoster Navigation
Cypress.Commands.add('visitDeepPoster', (subpage = '') => {
  const page = subpage ? `deepposter${subpage}` : 'deepposter'
  cy.visit(`/wp-admin/admin.php?page=${page}`)
})

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

Cypress.Commands.add('debugPage', () => {
  cy.log('Debugging Seitenstruktur');
  
  cy.document().then((doc) => {
    // Logge den HTML-Inhalt des Body
    cy.log('Body HTML Struktur:');
    cy.log(doc.body.innerHTML);
    
    // Überprüfe spezifische Elemente
    const aiGenerator = doc.querySelector('.ai-generator');
    const deepposterForm = doc.querySelector('#deepposter-form');
    const deepposterPrompt = doc.querySelector('#deepposter-prompt');
    
    cy.log('Gefundene Elemente:');
    cy.log(`ai-generator: ${aiGenerator ? 'gefunden' : 'nicht gefunden'}`);
    cy.log(`deepposter-form: ${deepposterForm ? 'gefunden' : 'nicht gefunden'}`);
    cy.log(`deepposter-prompt: ${deepposterPrompt ? 'gefunden' : 'nicht gefunden'}`);
  });
}); 