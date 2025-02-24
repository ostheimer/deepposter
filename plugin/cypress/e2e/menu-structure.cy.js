describe('DeepPoster Menu Structure Tests', () => {
    beforeEach(() => {
        cy.log('Starting menu structure test');
        cy.login();
        cy.visit('/wp-admin/admin.php?page=deepposter', {
            timeout: 30000,
            retryOnNetworkFailure: true
        });
    });

    it('should have correct menu structure', () => {
        // Überprüfe Hauptmenü
        cy.get('#adminmenu')
            .find('li.menu-top')
            .contains('DeepPoster')
            .should('be.visible')
            .then($menu => {
                cy.log('Found DeepPoster menu:', $menu.text());
            });

        // Überprüfe Untermenüs
        const expectedSubmenus = [
            { text: 'Generator', href: 'page=deepposter' },
            { text: 'Prompts verwalten', href: 'post_type=deepposter_prompt' },
            { text: 'Einstellungen', href: 'page=deepposter-settings' },
            { text: 'System Status', href: 'page=deepposter-status' }
        ];

        // Überprüfe jeden Menüpunkt einzeln
        expectedSubmenus.forEach(submenu => {
            cy.log('Checking submenu:', submenu.text);
            cy.get('#adminmenu')
                .find('.wp-submenu li a')
                .contains(submenu.text)
                .should('be.visible')
                .and('have.attr', 'href')
                .and('include', submenu.href);
        });

        // Hole die aktuelle Anzahl der Untermenüs für Debugging
        cy.get('#adminmenu')
            .find('li.menu-top')
            .contains('DeepPoster')
            .parent()
            .find('.wp-submenu li:not(.wp-submenu-head)')
            .then($items => {
                cy.log(`Found ${$items.length} submenu items. Note: WordPress könnte zusätzliche Menüpunkte anzeigen.`);
                $items.each((i, el) => {
                    cy.log(`Menu item ${i + 1}:`, el.textContent.trim());
                });
            });
            
        // Stelle sicher, dass alle erwarteten Untermenüs vorhanden sind
        // (statt genau 4 Einträge zu erwarten)
        expectedSubmenus.forEach(submenu => {
            cy.get('#adminmenu')
                .find('li.menu-top')
                .contains('DeepPoster')
                .parent()
                .find('.wp-submenu li:not(.wp-submenu-head)')
                .contains(submenu.text)
                .should('be.visible');
        });
    });

    it('should have correct generator page elements', () => {
        cy.log('Checking generator page elements');
        
        // Überprüfe Generator-Seite Elemente
        const elements = [
            '#promptSelect',
            '#promptText',
            '#categorySelect',
            '#articleCount',
            '#publishImmediately',
            '#savePrompt',
            '#generateButton'
        ];

        elements.forEach(selector => {
            cy.log('Checking element:', selector);
            cy.get(selector).should('exist').and('be.visible');
        });
    });

    it('should navigate to correct pages and show correct content', () => {
        // Test Generator (Startseite)
        cy.log('Testing Generator page');
        cy.get('#adminmenu')
            .contains('Generator')
            .click();
        cy.get('h1').should('be.visible');
        cy.get('#promptSelect').should('exist');
        cy.get('#categorySelect').should('exist');

        // Test Prompts verwalten
        cy.log('Testing Prompts page');
        cy.get('#adminmenu')
            .contains('Prompts verwalten')
            .click();
        cy.url().should('include', 'post_type=deepposter_prompt');

        // Zurück zur Generator-Seite
        cy.visit('/wp-admin/admin.php?page=deepposter', {
            timeout: 30000,
            retryOnNetworkFailure: true
        });

        // Test Einstellungen
        cy.log('Testing Settings page');
        cy.get('#adminmenu')
            .contains('Einstellungen')
            .click();
        cy.url().should('include', 'page=deepposter-settings');
        cy.get('#api_provider').should('exist');
        cy.get('#openai_key').should('exist');

        // Test System Status
        cy.log('Testing System Status page');
        cy.get('#adminmenu')
            .contains('System Status')
            .click();
        cy.url().should('include', 'page=deepposter-status');
        cy.get('h1').should('be.visible');
    });
}); 