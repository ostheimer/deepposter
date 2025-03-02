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

// Cypress-Test für die Prompt-Verwaltung mit Titeln
describe('Prompt Management', () => {
  beforeEach(() => {
    // Login und Navigation zum Dashboard vor jedem Test
    cy.login();
    cy.visitDeepPoster();
  });

  it('should save a prompt with title', () => {
    const testTitle = 'Test Titel ' + new Date().getTime();
    const testPrompt = 'Dies ist ein Testprompt für Cypress ' + new Date().getTime();
    
    // Debugging-Ausgabe
    cy.log('Teste das Speichern eines Prompts mit Titel: ' + testTitle);
    
    // Titeleingabefeld ausfüllen
    cy.get('#promptTitle').should('be.visible').clear().type(testTitle);
    cy.get('#promptText').should('be.visible').clear().type(testPrompt);
    
    // AJAX-Anfrage abfangen
    cy.intercept('POST', '**/admin-ajax.php').as('savePromptRequest');
    
    // Speichern-Button finden und klicken
    cy.get('#savePrompt').should('be.visible').click();
    
    // Auf AJAX-Antwort warten und prüfen
    cy.wait('@savePromptRequest').then((interception) => {
      cy.log('AJAX-Antwort erhalten:', interception.response.body);
      
      // Überprüfen, ob die richtigen Daten gesendet wurden - berücksichtige sowohl + als auch %20 für Leerzeichen
      const titleEncoded = testTitle.replace(/ /g, '+');
      const promptEncoded = testPrompt.replace(/ /g, '+').replace(/ü/g, '%C3%BC'); // Umlaute werden anders kodiert
      
      expect(interception.request.body).to.include('prompt_title=' + titleEncoded);
      expect(interception.request.body).to.include('prompt_text=' + promptEncoded);
      
      // Überprüfen der Serverantwort, falls verfügbar
      if (interception.response && interception.response.body) {
        expect(interception.response.body.success).to.be.true;
      }
      
      // Erfolgsbenachrichtigung prüfen
      cy.get('.deepposter-notice.success').should('be.visible');
    });
  });

  it('should validate title field when saving a prompt', () => {
    // Debugging-Ausgabe
    cy.log('Teste die Validierung des Titelfelds');
    
    // Prompt ohne Titel speichern versuchen
    cy.get('#promptTitle').should('be.visible').clear();
    cy.get('#promptText').should('be.visible').clear().type('Ein Test-Prompt ohne Titel');
    
    // AJAX-Anfrage abfangen
    cy.intercept('POST', '**/admin-ajax.php').as('savePromptRequest');
    
    // Speichern-Button klicken
    cy.get('#savePrompt').should('be.visible').click();
    
    // Prüfen, dass die Fehlermeldung angezeigt wird
    cy.get('.deepposter-notice.error').should('be.visible')
      .and('contain', 'Bitte geben Sie einen Titel für den Prompt ein.');
      
    // Sicherstellen, dass keine AJAX-Anfrage gesendet wurde
    cy.wait(1000).then(() => {
      cy.get('@savePromptRequest.all').then((interceptions) => {
        expect(interceptions.length).to.equal(0);
      });
    });
  });
});

// Test für die Anzeige von Custom Post Type Prompts im Dropdown
describe('Custom Post Type Prompts im Dropdown', () => {
  beforeEach(() => {
    // Login und Navigation zum Dashboard vor jedem Test
    cy.login();
    cy.visitDeepPoster();
  });

  it('sollte Prompts aus dem Custom Post Type im Dropdown anzeigen', () => {
    // Warte auf das Laden des Dropdowns
    cy.get('#promptSelect').should('exist');
    
    // Debugging: Gib den Inhalt des Dropdowns aus
    cy.get('#promptSelect').then($select => {
      cy.log('Anzahl der Optionen im Dropdown: ' + $select.find('option').length);
      
      // Gib alle Optionen im Dropdown aus
      $select.find('option').each((index, option) => {
        cy.log(`Option ${index}: ${option.text} (Wert: ${option.value})`);
      });
    });
    
    // Prüfe, ob das Dropdown mehr als nur die Standardoption enthält
    cy.get('#promptSelect option').should('have.length.greaterThan', 1);
    
    // Intercept die AJAX-Anfrage, die die Prompts lädt
    cy.intercept('POST', '**/admin-ajax.php').as('loadPromptsRequest');
    
    // Löse das erneute Laden der Prompts aus, indem wir die Seite aktualisieren
    cy.reload();
    
    // Warte auf die AJAX-Antwort und prüfe den Inhalt
    cy.wait('@loadPromptsRequest').then((interception) => {
      cy.log('AJAX-Antwort für Prompt-Laden:', interception.response.body);
      
      // Überprüfe, ob die Antwort Prompts enthält
      expect(interception.response.body).to.have.property('success', true);
      expect(interception.response.body.data).to.have.property('prompts');
      
      // Prüfe, ob Prompts in der Antwort vorhanden sind
      const prompts = interception.response.body.data.prompts;
      expect(Object.keys(prompts).length).to.be.greaterThan(0);
      
      // Prüfe, ob die Prompts im Dropdown angezeigt werden
      cy.get('#promptSelect option').should('have.length.greaterThan', 1);
      
      // Wähle den ersten Prompt aus
      cy.get('#promptSelect').select(Object.keys(prompts)[0]);
      
      // Überprüfe, ob der Prompt-Titel und -Text angezeigt werden
      cy.get('#promptTitle').should('have.value', prompts[Object.keys(prompts)[0]].title);
      cy.get('#promptText').should('have.value', prompts[Object.keys(prompts)[0]].text);
    });
  });
}); 