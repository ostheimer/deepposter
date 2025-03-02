/**
 * Cypress Test für das DeepPoster Datenbank-Reparatur-Tool
 * 
 * Dieser Test überprüft die Funktionalität des Reparatur-Tools,
 * das über die Konsole ausgeführt wird.
 */

describe('DeepPoster Datenbank-Reparatur-Tool (Konsolen-Version)', () => {
  beforeEach(() => {
    // Login in WordPress Admin
    cy.visit('/wp-login.php');
    cy.get('#user_login').type('admin');
    cy.get('#user_pass').type('password');
    cy.get('#wp-submit').click();
    
    // Navigiere zur DeepPoster-Seite
    cy.visit('/wp-admin/admin.php?page=deepposter');
    
    // Warte, bis die Seite geladen ist
    cy.get('h1').should('contain', 'DeepPoster');
  });

  it('sollte das Reparatur-Tool über die Konsole ausführen können', () => {
    // Führe das Reparatur-Skript in der Konsole aus
    cy.window().then((win) => {
      // Lade das Skript
      cy.readFile('assets/repair-console.js').then((script) => {
        // Führe das Skript in der Konsole aus
        cy.log('Führe Reparatur-Skript aus');
        win.eval(script);
        
        // Überprüfe, ob das UI-Element erstellt wurde
        cy.get('h2').contains('DeepPoster Datenbank-Reparatur').should('be.visible');
        cy.get('#startRepair').should('be.visible');
        
        // Klicke auf den Reparatur-Button
        cy.get('#startRepair').click();
        
        // Warte auf die Ergebnisse
        cy.get('#repairResults', { timeout: 10000 }).should('be.visible');
        
        // Überprüfe, ob die Ergebnisse angezeigt werden
        cy.get('#repairOutput').should('exist');
        
        // Schließe das Reparatur-Tool
        cy.get('#closeRepair').click();
        cy.get('h2').contains('DeepPoster Datenbank-Reparatur').should('not.exist');
      });
    });
  });

  it('sollte nach der Reparatur keine Fehler mehr in der Konsole anzeigen', () => {
    // Führe das Reparatur-Skript aus und überprüfe dann die Konsole
    cy.window().then((win) => {
      // Speichere die ursprünglichen Konsolenmethoden
      const originalConsoleError = win.console.error;
      
      // Überwache Konsolenfehler
      let consoleErrors = [];
      win.console.error = (msg) => {
        consoleErrors.push(msg);
        originalConsoleError(msg);
      };
      
      // Lade das Skript
      cy.readFile('assets/repair-console.js').then((script) => {
        // Führe das Skript in der Konsole aus
        win.eval(script);
        
        // Klicke auf den Reparatur-Button
        cy.get('#startRepair').click();
        
        // Warte auf die Ergebnisse
        cy.get('#repairResults', { timeout: 10000 }).should('be.visible');
        
        // Schließe das Reparatur-Tool
        cy.get('#closeRepair').click();
        
        // Lade die Seite neu
        cy.reload();
        
        // Warte, bis die Seite geladen ist
        cy.get('h1').should('contain', 'DeepPoster');
        
        // Überprüfe, ob der Fehler "Cannot create item with duplicate id" nicht mehr auftritt
        cy.wait(2000).then(() => {
          const hasDuplicateIdError = consoleErrors.some(error => 
            typeof error === 'string' && error.includes('Cannot create item with duplicate id')
          );
          expect(hasDuplicateIdError).to.be.false;
        });
      });
    });
  });

  it('sollte die Dropdown-Liste der Prompts korrekt anzeigen', () => {
    // Führe zuerst das Reparatur-Skript aus
    cy.window().then((win) => {
      cy.readFile('assets/repair-console.js').then((script) => {
        win.eval(script);
        cy.get('#startRepair').click();
        cy.get('#repairResults', { timeout: 10000 }).should('be.visible');
        cy.get('#closeRepair').click();
        
        // Lade die Seite neu
        cy.reload();
        
        // Warte, bis die Seite geladen ist
        cy.get('h1').should('contain', 'DeepPoster');
        
        // Überprüfe, ob die Dropdown-Liste der Prompts korrekt angezeigt wird
        cy.get('select[name="prompt"]').should('exist');
        cy.get('select[name="prompt"] option').should('have.length.gt', 1);
        
        // Überprüfe, ob ein Prompt ausgewählt werden kann
        cy.get('select[name="prompt"]').select(1);
        cy.get('select[name="prompt"]').should('not.have.value', '');
      });
    });
  });
}); 