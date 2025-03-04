describe('DeepPoster Prompt Auswahl', () => {
    it('sollte einen Prompt in der Vorschau anzeigen, wenn einer ausgewählt wird', () => {
        // Besuche die Generator-Seite direkt
        cy.visit('/wp-admin/admin.php?page=deepposter');
        
        // Warte auf das Laden der Seite und überprüfe ob wichtige Elemente vorhanden sind
        cy.get('#promptSelect').should('exist');
        cy.get('#selectedPromptContent').should('exist');
        
        // Warte auf das Laden der Dropdown-Options
        cy.get('#promptSelect option').should('have.length.at.least', 1);
        
        // Führe eine Ausgabe des DOM-Inhalts durch, um zu sehen was geladen wurde
        cy.get('#promptSelect').then(($select) => {
            cy.log('PromptSelect Options:', $select.html());
        });
        
        // Wähle den ersten Eintrag im Dropdown aus (falls mehr als einer vorhanden ist)
        cy.get('#promptSelect option').then(($options) => {
            if ($options.length > 1) {
                // Wähle die erste Option, die nicht leer ist
                const firstNonEmptyOption = Array.from($options).find(opt => opt.value !== '');
                if (firstNonEmptyOption) {
                    cy.log('Wähle Option mit Wert:', firstNonEmptyOption.value);
                    cy.get('#promptSelect').select(firstNonEmptyOption.value);
                    
                    // Warte auf die AJAX-Anfrage
                    cy.wait(2000);
                    
                    // Überprüfe, ob der Inhalt angezeigt wird
                    cy.get('#selectedPromptContent').should('not.be.empty');
                    cy.get('#selectedPromptContent').should('not.contain', 'Lade Prompt-Inhalt...');
                    cy.get('#selectedPromptContent').should('not.contain', 'Fehler beim Laden des Prompts');
                }
            } else {
                cy.log('Keine auswählbaren Prompts gefunden.');
            }
        });
    });
}); 