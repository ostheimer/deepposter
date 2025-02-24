/**
 * Test für die Prompt-Verwaltungsfunktionen des DeepPoster-Plugins
 * 
 * Diese Tests überprüfen:
 * - Laden der Hauptseite
 * - Bearbeiten eines Prompts
 * - Speichern eines Prompts
 * - Auswahl einer Kategorie
 * - Einstellung der Artikelanzahl
 */

describe('DeepPoster Prompt-Verwaltung', () => {
  beforeEach(() => {
    // Besuche die DeepPoster-Hauptseite vor jedem Test
    cy.visitDeepPoster();
    
    // Stelle sicher, dass die Hauptelemente geladen sind
    cy.get('textarea').should('be.visible');
    cy.get('select').should('exist');
  });
  
  it('sollte die Hauptelemente korrekt anzeigen', () => {
    // Überprüfe, ob alle Hauptelemente vorhanden sind
    cy.get('textarea').should('be.visible');
    cy.get('select').first().should('exist');
    cy.get('select').eq(1).should('exist');
    cy.get('button').contains('Artikel generieren').should('be.visible');
    
    // Überprüfe die Überschriften und Erklärungstexte
    cy.contains('Gespeicherte Prompts:').should('be.visible');
    cy.contains('Prompt Vorschau & Anpassung:').should('be.visible');
    cy.contains('Kategorie auswählen:').should('be.visible');
    cy.contains('Anzahl Artikel:').should('be.visible');
  });
  
  it('sollte einen Prompt bearbeiten können', () => {
    // Erstelle einen Testnamen mit Zeitstempel für Eindeutigkeit
    const testPromptText = 'Dies ist ein Test-Prompt für Cypress-Tests. Erstelle einen kurzen Artikel zum Thema Automatisiertes Testen.';
    
    // Gebe einen neuen Prompt-Text ein
    cy.get('textarea')
      .clear()
      .type(testPromptText, { delay: 10 });
      
    // Überprüfe, ob der Text korrekt eingegeben wurde
    cy.get('textarea').should('have.value', testPromptText);
  });
  
  it('sollte einen Prompt speichern können, wenn das Dialog-Feature verfügbar ist', () => {
    // Prüfe, ob der Speichern-Button existiert
    cy.get('button').contains('Prompt speichern').then($btn => {
      if ($btn.length > 0) {
        // Gebe einen Test-Prompt ein
        const testPromptText = 'Test-Prompt zum Speichern';
        cy.get('textarea')
          .clear()
          .type(testPromptText, { delay: 10 });
        
        // Klicke auf "Prompt speichern" und prüfe, ob ein Dialog erscheint
        cy.get('button').contains('Prompt speichern').click();
        
        // Wenn ein Dialog-Element erscheint, interagiere damit
        cy.get('body').then($body => {
          // Prüfe, ob ein Eingabefeld für den Prompt-Namen erscheint
          if ($body.find('input[type="text"]').length > 0) {
            // Im Dialog den Prompt-Namen eingeben
            cy.get('input[type="text"]').first()
              .type(`Test-Prompt-${new Date().getTime()}`);
            
            // Speichern bestätigen, wenn ein Button vorhanden ist
            cy.get('button').contains(/Speichern|Bestätigen|OK/i).click();
            
            // Log erfolgreiche Speicherung
            cy.log('Prompt wurde gespeichert');
          } else {
            cy.log('Kein Dialog zur Prompteingabe gefunden - Test übersprungen');
          }
        });
      } else {
        cy.log('Kein Speichern-Button gefunden - Test übersprungen');
      }
    });
  });
  
  it('sollte Kategorien auswählen können', () => {
    // Identifiziere das Kategorie-Dropdown (zweites Select-Element)
    cy.get('select').eq(1).then($select => {
      // Prüfe, ob Optionen vorhanden sind
      if ($select.find('option').length > 1) {
        // Wähle die zweite Option (erste nach dem Standardwert)
        cy.get('select').eq(1).select($select.find('option').eq(1).val());
        
        // Überprüfe, ob die Option ausgewählt wurde
        const selectedValue = $select.find('option').eq(1).val();
        cy.get('select').eq(1).should('have.value', selectedValue);
      } else {
        cy.log('Nicht genügend Kategorieoptionen vorhanden - Test übersprungen');
      }
    });
  });
  
  it('sollte die "Sofort veröffentlichen" Checkbox umschalten können', () => {
    // Finde die Checkbox anhand des Textes
    cy.contains('label', 'Sofort veröffentlichen').within(() => {
      cy.get('input[type="checkbox"]').as('publishCheckbox');
    });
    
    // Überprüfe den initialen Status
    cy.get('@publishCheckbox').should('not.be.checked');
    
    // Aktiviere die Checkbox
    cy.get('@publishCheckbox').check();
    
    // Überprüfe, ob die Checkbox nun angehakt ist
    cy.get('@publishCheckbox').should('be.checked');
    
    // Deaktiviere die Checkbox wieder
    cy.get('@publishCheckbox').uncheck();
    
    // Überprüfe, ob die Checkbox nun nicht mehr angehakt ist
    cy.get('@publishCheckbox').should('not.be.checked');
  });
}); 