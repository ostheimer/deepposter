describe('DeepPoster Admin Tests', () => {
    beforeEach(() => {
        cy.login();
        cy.visit('/wp-admin/admin.php?page=deepposter');
    });

    it('sollte die Struktur der Einstellungsseite analysieren', () => {
        cy.get('#deepposter-form').should('exist');
        cy.get('#deepposter-prompt').should('exist');
        cy.get('#deepposter-category').should('exist');
        cy.get('#deepposter-count').should('exist');
        cy.get('#deepposter-publish').should('exist');
        cy.get('button[type="submit"]').should('exist');
    });

    it('sollte einen Artikel mit Standard-Prompt generieren', () => {
        cy.get('#deepposter-category').select('1');
        
        cy.get('#deepposter-prompt')
            .should('have.value')
            .and('include', 'Du bist ein professioneller Content-Ersteller')
            .and('include', 'Allgemein');

        cy.get('#deepposter-count').select('1');
        
        cy.get('button[type="submit"]').click();

        cy.get('#deepposter-result')
            .should('be.visible')
            .and('contain', 'Artikel erfolgreich generiert');

        cy.get('#deepposter-result li')
            .should('have.length', 1)
            .and('contain', 'Bearbeiten')
            .and('contain', 'Status: draft');
    });

    it('sollte mehrere Artikel generieren', () => {
        cy.get('#deepposter-category').select('1');
        
        cy.get('#deepposter-count').select('3');
        
        cy.get('button[type="submit"]').click();

        cy.get('#deepposter-result')
            .should('be.visible')
            .and('contain', 'Artikel erfolgreich generiert');

        cy.get('#deepposter-result li')
            .should('have.length', 3);
    });

    it('sollte Artikel sofort veröffentlichen', () => {
        cy.get('#deepposter-category').select('1');
        
        cy.get('#deepposter-publish').check();
        
        cy.get('button[type="submit"]').click();

        cy.get('#deepposter-result')
            .should('be.visible')
            .and('contain', 'Artikel erfolgreich generiert');

        cy.get('#deepposter-result li')
            .should('contain', 'Status: publish');
    });

    it('sollte benutzerdefiniertes Prompt verwenden', () => {
        cy.get('#deepposter-category').select('1');
        
        const customPrompt = 'Erstelle einen kurzen Test-Artikel für die Kategorie [KATEGORIE] mit maximal 100 Wörtern.';
        cy.get('#deepposter-prompt')
            .clear()
            .type(customPrompt);
        
        cy.get('button[type="submit"]').click();

        cy.get('#deepposter-result')
            .should('be.visible')
            .and('contain', 'Artikel erfolgreich generiert');

        cy.get('#deepposter-result li a')
            .first()
            .click();

        cy.get('.editor-post-title__input')
            .should('exist');
        
        cy.get('.block-editor-block-list__layout')
            .should('exist')
            .and('not.be.empty');
    });

    it('sollte Fehler bei fehlender Kategorie anzeigen', () => {
        cy.get('button[type="submit"]').click();

        cy.get('#deepposter-result')
            .should('be.visible')
            .and('contain', 'Bitte wählen Sie eine Kategorie aus');
    });
}); 