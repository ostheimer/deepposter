describe('DeepPoster Settings Page', () => {
    beforeEach(() => {
        // Login vor jedem Test (verwendet gespeicherte Session)
        cy.login('admin', 'password');
    });

    it('should not show "Prompts geladen" message on settings page', () => {
        // Besuche die Einstellungsseite
        cy.visit('/wp-admin/admin.php?page=deepposter-settings');
        
        // Warte auf das Laden der Seite
        cy.get('body').should('have.class', 'wp-admin');
        cy.get('.wrap').should('exist');
        
        // Warte auf mögliche AJAX-Anfragen
        cy.wait(2000);

        // Überprüfe, dass die Meldung NICHT vorhanden ist
        cy.get('.notice-success').should('not.exist');
        cy.contains('Prompts geladen').should('not.exist');
    });

    it('should show "Prompts geladen" message only on generator page', () => {
        // Besuche die Generator-Seite
        cy.visit('/wp-admin/admin.php?page=deepposter');
        
        // Warte auf das Laden der Seite
        cy.get('body').should('have.class', 'wp-admin');
        cy.get('.wrap').should('exist');
        
        // Warte auf AJAX-Anfragen
        cy.wait(2000);

        // Überprüfe, dass die Meldung vorhanden ist
        cy.get('.notice-success').should('exist');
        cy.contains('Prompts geladen').should('be.visible');
    });
}); 