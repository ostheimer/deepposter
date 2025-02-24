/**
 * Debugging-Test zur Analyse der Seitenstruktur des DeepPoster-Plugins
 */

describe('DeepPoster Debugging', () => {
  it('sollte die Seitenstruktur analysieren', () => {
    // Besuche die DeepPoster-Hauptseite 
    cy.visitDeepPoster();
    
    // Warte auf das vollständige Laden der Seite
    cy.wait(2000);
    
    // Untersuche den HTML-Code der Seite
    cy.document().then((doc) => {
      // Gib den Titel und Body-Klassen aus
      cy.log('Page Title:', doc.title);
      cy.log('Body Classes:', doc.body.className);
      
      // Untersuche alle Formularelemente
      const forms = doc.querySelectorAll('form');
      cy.log(`Gefundene Formulare: ${forms.length}`);
      
      forms.forEach((form, index) => {
        cy.log(`Form ${index + 1}:`, {
          id: form.id || 'Keine ID',
          className: form.className || 'Keine Klasse',
          action: form.action || 'Keine Action',
          method: form.method || 'Keine Methode'
        });
        
        // Untersuche alle Inputs und Selects innerhalb des Formulars
        const inputs = form.querySelectorAll('input, select, textarea, button');
        cy.log(`Form ${index + 1} Elemente: ${inputs.length}`);
        
        inputs.forEach((input, idx) => {
          cy.log(`Element ${idx + 1}:`, {
            tagName: input.tagName,
            type: input.type || 'Kein Typ',
            id: input.id || 'Keine ID',
            name: input.name || 'Kein Name',
            className: input.className || 'Keine Klasse',
            value: input.value || 'Kein Wert'
          });
        });
      });
      
      // Untersuche wichtige Elemente auf der Seite
      [
        'promptText', 'promptSelect', 'categorySelect', 'savePrompt', 'generateArticle',
        'publishImmediately', 'articleCount'
      ].forEach(id => {
        const element = doc.getElementById(id);
        if (element) {
          cy.log(`Element #${id} gefunden:`, {
            tagName: element.tagName,
            className: element.className,
            type: element.type || 'Kein Typ'
          });
        } else {
          cy.log(`Element #${id} NICHT gefunden`);
          
          // Suche nach Elementen mit ähnlichem Namen
          const elementsByName = doc.querySelectorAll(`[name="${id}"]`);
          if (elementsByName.length > 0) {
            cy.log(`Element mit name="${id}" gefunden: ${elementsByName.length}`);
          }
          
          // Suche nach Elementen, die den Text enthalten
          const textElements = Array.from(doc.querySelectorAll('*')).filter(el => 
            el.textContent && el.textContent.toLowerCase().includes(id.toLowerCase())
          );
          if (textElements.length > 0) {
            cy.log(`${textElements.length} Elemente enthalten den Text "${id}"`);
          }
        }
      });
      
      // Textarea mit Prompt-Text untersuchen
      const textareas = doc.querySelectorAll('textarea');
      cy.log(`Textareas auf der Seite: ${textareas.length}`);
      textareas.forEach((textarea, idx) => {
        cy.log(`Textarea ${idx + 1}:`, {
          id: textarea.id || 'Keine ID',
          name: textarea.name || 'Kein Name',
          className: textarea.className || 'Keine Klasse'
        });
      });
      
      // Alle Buttons untersuchen
      const buttons = doc.querySelectorAll('button, input[type="button"], input[type="submit"]');
      cy.log(`Buttons auf der Seite: ${buttons.length}`);
      buttons.forEach((button, idx) => {
        cy.log(`Button ${idx + 1}:`, {
          tagName: button.tagName,
          id: button.id || 'Keine ID',
          className: button.className || 'Keine Klasse',
          text: button.textContent || button.value || 'Kein Text'
        });
      });
      
      // Selects untersuchen
      const selects = doc.querySelectorAll('select');
      cy.log(`Selects auf der Seite: ${selects.length}`);
      selects.forEach((select, idx) => {
        cy.log(`Select ${idx + 1}:`, {
          id: select.id || 'Keine ID',
          name: select.name || 'Kein Name',
          className: select.className || 'Keine Klasse',
          options: select.options.length
        });
        
        // Untersuche die Optionen
        const options = Array.from(select.options).map(option => ({
          value: option.value,
          text: option.text
        }));
        cy.log(`Optionen für Select ${idx + 1}:`, options);
      });
    });
    
    // Screenshot für visuelle Inspektion
    cy.screenshot('debug-page-structure');
  });
}); 