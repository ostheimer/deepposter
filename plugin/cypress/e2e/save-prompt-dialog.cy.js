/**
 * Test für die Prompt-Speicherfunktion (mit direktem Titelfeld)
 * 
 * Diese Tests überprüfen:
 * - Das Speichern eines Prompts mit direktem Titelfeld
 * - Das Bearbeiten und Anzeigen des aktuellen Prompts
 */

describe('DeepPoster Prompt-Speicherdialog', () => {
  beforeEach(() => {
    // Besuche die DeepPoster-Hauptseite vor jedem Test
    cy.visitDeepPoster();
    
    // Stelle sicher, dass die Hauptelemente geladen sind
    cy.get('#promptTitle').should('be.visible');
    cy.get('#promptText').should('be.visible');
    cy.get('#savePrompt').should('be.visible');
  });
  
  it('sollte einen Prompt speichern können, wenn der "Prompt speichern"-Button vorhanden ist', () => {
    // Testdaten erstellen
    const testTitle = 'Test Prompt ' + new Date().getTime();
    const testText = 'Dies ist ein Test-Prompt für die Cypress-Tests.';
    
    // Promtptitel eingeben
    cy.get('#promptTitle')
      .clear()
      .type(testTitle);
      
    // Prompt-Text eingeben
    cy.get('#promptText')
      .clear()
      .type(testText);
    
    // AJAX-Anfrage zum Speichern des Prompts abfangen
    cy.intercept('POST', '**/admin-ajax.php').as('savePromptRequest');
    
    // Auf "Prompt speichern" klicken
    cy.get('#savePrompt').click();
    
    // Auf die AJAX-Antwort warten
    cy.wait('@savePromptRequest').then((interception) => {
      expect(interception.request.body).to.include('action=deepposter_save_prompt');
      
      // Wenn eine Antwort vorhanden ist, prüfen wir deren Inhalt
      if (interception.response && interception.response.body) {
        expect(interception.response.body.success).to.be.true;
      }
    });
    
    // Screenshot der Seite nach erfolgreicher Speicherung
    cy.screenshot('prompt-speichern-neues-format');
  });
  
  it('sollte den aktuellen Prompt anzeigen und ihn bearbeiten können', () => {
    // Testdaten erstellen
    const testText = 'Dies ist ein bearbeiteter Test-Prompt. ' + new Date().getTime();
    
    // Prompt-Text bearbeiten
    cy.get('#promptText')
      .clear()
      .type(testText);
      
    // Überprüfen, ob der Text korrekt eingegeben wurde
    cy.get('#promptText').should('have.value', testText);
    
    // Überprüfen, ob das Titelfeld vorhanden und bearbeitbar ist
    cy.get('#promptTitle')
      .should('be.visible')
      .clear()
      .type('Bearbeiteter Prompt-Titel');
      
    cy.get('#promptTitle').should('have.value', 'Bearbeiteter Prompt-Titel');
  });
}); 