describe('DeepPoster Menu Structure Tests', () => {
    beforeEach(() => {
        cy.login();
        cy.visit('/wp-admin/admin.php?page=deepposter');
    });

    it('should have correct menu structure', () => {
        // Überprüfe Hauptmenü
        cy.get('#adminmenu')
            .find('a[href="admin.php?page=deepposter"]')
            .should('contain', 'DeepPoster');

        // Überprüfe Untermenüs
        const expectedSubmenus = [
            { text: 'Generator', href: 'admin.php?page=deepposter' },
            { text: 'Alle Prompts', href: 'edit.php?post_type=deepposter_prompt' },
            { text: 'Einstellungen', href: 'admin.php?page=deepposter-settings' },
            { text: 'System Status', href: 'admin.php?page=deepposter-status' }
        ];

        expectedSubmenus.forEach(submenu => {
            cy.get('#adminmenu')
                .find(`a[href="${submenu.href}"]`)
                .should('contain', submenu.text)
                .should('be.visible');
        });

        // Stelle sicher, dass es keine doppelten Menüeinträge gibt
        cy.get('#adminmenu')
            .find('a[href="admin.php?page=deepposter"]')
            .should('have.length', 2); // Einer für Hauptmenü, einer für Generator

        cy.get('#adminmenu')
            .find('a[href="edit.php?post_type=deepposter_prompt"]')
            .should('have.length', 1);
    });

    it('should have correct generator page elements', () => {
        // Überprüfe Generator-Seite Elemente
        cy.get('#promptSelect').should('exist');
        cy.get('#promptText').should('exist');
        cy.get('#categorySelect').should('exist');
        cy.get('#articleCount').should('exist');
        cy.get('#publishImmediately').should('exist');
        cy.get('#savePrompt').should('exist');
        cy.get('#generateButton').should('exist');
    });

    it('should navigate to correct pages', () => {
        // Test Generator
        cy.get('#adminmenu')
            .find('a[href="admin.php?page=deepposter"]')
            .first()
            .click();
        cy.get('h1').should('contain', 'DeepPoster Generator');

        // Test Prompts Liste
        cy.get('#adminmenu')
            .find('a[href="edit.php?post_type=deepposter_prompt"]')
            .click();
        cy.get('h1').should('contain', 'Prompts');

        // Test Einstellungen
        cy.get('#adminmenu')
            .find('a[href="admin.php?page=deepposter-settings"]')
            .click();
        cy.get('h1').should('contain', 'DeepPoster Settings');

        // Test System Status
        cy.get('#adminmenu')
            .find('a[href="admin.php?page=deepposter-status"]')
            .click();
        cy.get('h1').should('contain', 'DeepPoster System Status');
    });
}); 