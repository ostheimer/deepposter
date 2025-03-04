describe('DeepPoster Prompt Vorschau', () => {
    beforeEach(() => {
        // Login vor jedem Test
        cy.login('admin', 'password');
    });

    it('sollte einen Prompt in der Vorschau anzeigen, wenn einer ausgewählt wird', () => {
        // Besuche die Generator-Seite
        cy.visit('/wp-admin/admin.php?page=deepposter');

        // Warte auf das Laden der Seite und der Prompts
        cy.get('#promptSelect').should('be.visible');
        cy.get('#promptSelect option').should('have.length.at.least', 2); // Mindestens die Standardoption und ein Prompt
        
        // "Prompts geladen" Meldung sollte angezeigt werden
        cy.get('#prompts-loaded-notice').should('exist');
        cy.get('#prompts-loaded-notice').contains('Prompts geladen');
        
        // Wähle einen Prompt aus dem Dropdown
        cy.get('#promptSelect').select(1); // Wähle den ersten Prompt (nicht die leere Option)
        
        // Warte auf die AJAX-Anfrage
        cy.wait(2000);
        
        // Überprüfe, ob der Inhalt im Preview-Bereich angezeigt wird
        cy.get('#selectedPromptContent').should('not.be.empty');
        cy.get('#selectedPromptContent').should('not.contain', 'Lade Prompt-Inhalt...');
        cy.get('#selectedPromptContent').should('not.contain', 'Fehler beim Laden des Prompts');
        
        // Überprüfe, ob die Erfolgsmeldung angezeigt wird
        cy.get('.notice-prompt-selected').should('exist');
        cy.get('.notice-prompt-selected').should('contain', 'Prompt');
        cy.get('.notice-prompt-selected').should('contain', 'geladen');
    });

    it('sollte die "Prompts geladen" Meldung schließen, wenn auf den X-Button geklickt wird', () => {
        // Besuche die Generator-Seite
        cy.visit('/wp-admin/admin.php?page=deepposter');
        
        // Warte auf das Laden der Prompts
        cy.get('#prompts-loaded-notice').should('exist');
        
        // Klicke auf den Schließen-Button
        cy.get('#prompts-loaded-notice .notice-dismiss').click();
        
        // Überprüfe, ob die Meldung verschwindet
        cy.get('#prompts-loaded-notice').should('not.exist');
    });
}); 