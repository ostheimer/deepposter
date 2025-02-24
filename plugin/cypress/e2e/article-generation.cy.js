/**
 * Test für die Artikelgenerierungsfunktion des DeepPoster-Plugins
 * 
 * Diese Tests überprüfen:
 * - Auswählen eines Prompts
 * - Auswählen einer Kategorie
 * - Einstellen der Artikelanzahl
 * - Klicken auf "Artikel generieren"
 * - Überprüfung des Generierungsprozesses
 */

describe('DeepPoster Artikelgenerierung', () => {
  beforeEach(() => {
    // Besuche die DeepPoster-Hauptseite vor jedem Test
    cy.visitDeepPoster();
    cy.log('Vorbereitung der Artikelgenerierungstests');
    
    // Stelle sicher, dass die Hauptelemente geladen sind
    cy.get('textarea').should('be.visible');
    cy.get('button').contains('Artikel generieren').should('be.visible');
  });
  
  it('sollte die Grundkonfiguration für die Artikelgenerierung vornehmen können', () => {
    // Gebe einen Testtext in das Prompt-Feld ein
    const testPrompt = 'Erstelle einen kurzen Testartikel für Cypress-Automatisierungstests.';
    cy.get('textarea')
      .clear()
      .type(testPrompt, { delay: 10 });
    
    // Wähle eine Kategorie aus (zweites Select-Element)
    cy.get('select').eq(1).then($select => {
      if ($select.find('option').length > 1) {
        // Wähle die zweite Option aus
        cy.get('select').eq(1).select($select.find('option').eq(1).val());
      } else {
        cy.log('Keine Kategorien zum Auswählen verfügbar');
      }
    });
    
    // Wähle die Anzahl der Artikel aus (drittes Select-Element, falls vorhanden)
    cy.get('select').eq(2).then($select => {
      if ($select.length > 0) {
        cy.get('select').eq(2).select('1');
      } else {
        cy.log('Kein Auswahlfeld für Artikelanzahl gefunden');
      }
    });
    
    // Stelle sicher, dass alle Konfigurationen korrekt eingestellt sind
    cy.get('textarea').should('have.value', testPrompt);
  });
  
  it('sollte auf den "Artikel generieren"-Button klicken und den Prozess simulieren', () => {
    // Stelle sicher, dass ein Prompt eingegeben ist
    cy.get('textarea').then($promptText => {
      if (!$promptText.val()) {
        const defaultPrompt = 'Erstelle einen kurzen Testbeitrag für Cypress.';
        cy.get('textarea').type(defaultPrompt, { delay: 10 });
      }
    });
    
    // Wähle eine Kategorie aus, falls noch keine ausgewählt ist
    cy.get('select').eq(1).then($select => {
      if ($select.find('option').length > 1 && (!$select.val() || $select.val() === '')) {
        cy.get('select').eq(1).select($select.find('option').eq(1).val());
      }
    });
    
    // Interceptiere den AJAX-Aufruf für die Artikelgenerierung
    cy.intercept('POST', '**/wp-admin/admin-ajax.php').as('ajaxRequest');
    
    // Klicke auf den "Artikel generieren"-Button
    cy.get('button').contains('Artikel generieren').click();
    
    // Warte auf den AJAX-Aufruf
    cy.wait('@ajaxRequest').then(interception => {
      cy.log('AJAX-Anfrage abgefangen:', interception);
    });
    
    // Erfolgreiche Anfrage simulieren - hier prüfen wir nur, ob der Button geklickt wurde
    cy.log('Artikelgenerierung wurde gestartet');
  });
  
  // Dieser Test ist als "skip" markiert, da er eine echte Artikelgenerierung auslösen würde
  it.skip('sollte einen Artikel vollständig generieren und das Ergebnis anzeigen', () => {
    // Grundkonfiguration
    const testPrompt = 'Erstelle einen sehr kurzen Testartikel für Cypress (maximal 100 Wörter).';
    cy.get('textarea')
      .clear()
      .type(testPrompt, { delay: 10 });
      
    // Kategorie auswählen (zweites Select-Element)
    cy.get('select').eq(1).then($select => {
      if ($select.find('option').length > 1) {
        cy.get('select').eq(1).select($select.find('option').eq(1).val());
      }
    });
    
    // Nur einen Artikel generieren (drittes Select-Element)
    cy.get('select').eq(2).then($select => {
      if ($select.length > 0) {
        cy.get('select').eq(2).select('1');
      }
    });
    
    // Artikel generieren
    cy.get('button').contains('Artikel generieren').click();
    
    // Warte auf Abschluss der Generierung (mit erhöhtem Timeout)
    // Da wir die genaue Struktur nicht kennen, suchen wir nach einem Container mit dem generierten Artikel
    cy.get('[id*="article"], [class*="article"]', { timeout: 60000 }).should('be.visible');
  });
  
  // Mocking des Generierungsprozesses, um API-Aufrufe zu vermeiden
  it('sollte den Generierungsprozess mit gemockten Daten simulieren', () => {
    // Interceptiere den API-Aufruf für die Artikelgenerierung
    cy.intercept('POST', '**/wp-admin/admin-ajax.php', (req) => {
      // Überprüfe, ob es sich um den API-Aufruf zur Artikelgenerierung handelt
      // Da wir den genauen Action-Namen nicht kennen, prüfen wir allgemeiner
      if (req.body && (req.body.includes('action=generate') || req.body.includes('generate_article'))) {
        req.reply({
          statusCode: 200,
          body: {
            success: true,
            data: {
              articles: [
                {
                  title: 'Testgenerierter Cypress-Artikel',
                  content: '<p>Dies ist ein automatisch generierter Testartikel für Cypress-Tests.</p><p>Der Inhalt wurde gemockt, um die API nicht zu belasten.</p>',
                  status: 'draft'
                }
              ],
              message: 'Artikel erfolgreich generiert'
            }
          }
        });
      }
    }).as('generateArticleRequest');
    
    // Grundkonfiguration für den Test
    cy.get('textarea').clear().type('Dies ist ein Test mit gemockten Daten');
    
    // Wähle eine Kategorie aus
    cy.get('select').eq(1).then($select => {
      if ($select.find('option').length > 1) {
        cy.get('select').eq(1).select($select.find('option').eq(1).val());
      }
    });
    
    // Klicke auf "Artikel generieren"
    cy.get('button').contains('Artikel generieren').click();
    
    // Warte auf den API-Aufruf
    cy.wait('@generateArticleRequest').then(interception => {
      if (interception.response && interception.response.statusCode === 200) {
        cy.log('Mock-Antwort wurde erfolgreich zurückgegeben');
      }
    });
  });
}); 