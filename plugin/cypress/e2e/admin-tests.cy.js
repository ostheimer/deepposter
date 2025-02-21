describe('DeepPoster Admin Tests', () => {
    beforeEach(() => {
        cy.login();
        cy.visitDeepPoster();
        
        // Aktiviere Konsolen-Logging
        cy.window().then((win) => {
            win.DEBUG = true;
        });

        // Simuliere erfolgreiche AJAX-Antwort
        cy.intercept('POST', '**/wp-admin/admin-ajax.php', (req) => {
            if (req.body.includes('action=deepposter_generate')) {
                req.reply({
                    statusCode: 200,
                    body: {
                        success: true,
                        data: [{
                            id: 1,
                            title: 'Test Artikel',
                            category: 'Test Kategorie',
                            status: 'draft',
                            editUrl: '/wp-admin/post.php?post=1&action=edit',
                            viewUrl: '/?p=1'
                        }]
                    }
                });
            }
        }).as('generateArticle');
    });

    it('sollte die Struktur der Einstellungsseite analysieren', () => {
        cy.get('#aiGeneratorForm').should('exist');
        cy.get('#promptText').should('exist');
        cy.get('#categorySelect').should('exist');
        cy.get('#articleCount').should('exist');
        cy.get('#publishImmediately').should('exist');
        cy.get('button[type="submit"]').should('exist');
    });

    it('sollte einen Artikel mit Standard-Prompt generieren', () => {
        // Überwache Konsolenausgaben
        cy.window().then((win) => {
            cy.spy(win.console, 'log').as('consoleLog');
        });

        // Wähle eine Kategorie
        cy.get('#categorySelect').select('1');
        
        // Prüfe, ob der Prompt die Kategorie enthält
        cy.get('#promptText')
            .invoke('val')
            .should('include', 'Du bist ein professioneller Content-Ersteller')
            .and('not.include', '[KATEGORIE]');

        // Wähle Anzahl der Artikel
        cy.get('#articleCount').select('1');
        
        // Aktiviere sofortige Veröffentlichung
        cy.get('#publishImmediately').check();
        
        // Sende das Formular ab und prüfe Button-Status
        cy.get('button[type="submit"]')
            .as('submitButton')
            .click()
            .should('have.class', 'button')
            .should('have.class', 'button-primary')
            .should('have.prop', 'disabled', true)
            .should('have.text', 'Generiere...');

        // Prüfe, ob die Ladeanimation angezeigt wird
        cy.get('#generationResults')
            .should('be.visible')
            .and('contain', 'Generiere Artikel...');

        // Warte auf die AJAX-Antwort
        cy.wait('@generateArticle');

        // Prüfe das Ergebnis
        cy.get('#generationResults')
            .should('contain', 'Artikel erfolgreich generiert')
            .within(() => {
                cy.get('.generation-item').should('have.length', 1);
                cy.get('.generation-actions a').should('have.length', 2);
                cy.get('.generation-actions a').first().should('contain', 'Bearbeiten');
                cy.get('.generation-actions a').last().should('contain', 'Ansehen');
            });

        // Prüfe Button-Status nach der Antwort
        cy.get('@submitButton')
            .should('have.prop', 'disabled', false)
            .should('have.text', 'Artikel generieren');

        // Prüfe die Debug-Ausgaben
        cy.get('@consoleLog').should('be.called');
    });

    it('sollte Fehler bei fehlender Kategorie anzeigen', () => {
        // Simuliere Fehler-Antwort
        cy.intercept('POST', '**/wp-admin/admin-ajax.php', {
            statusCode: 200,
            body: {
                success: false,
                data: 'Bitte wählen Sie eine Kategorie aus.'
            }
        }).as('generateError');

        // Klicke den Submit-Button ohne Kategorie auszuwählen
        cy.get('button[type="submit"]')
            .as('submitButton')
            .click();

        // Warte auf die AJAX-Antwort
        cy.wait('@generateError');

        // Prüfe die Fehlermeldung
        cy.get('#generationResults')
            .should('be.visible')
            .within(() => {
                cy.get('.notice-error')
                    .should('exist')
                    .and('contain', 'Bitte wählen Sie eine Kategorie aus');
            });

        // Prüfe Button-Status nach der Antwort
        cy.get('@submitButton')
            .should('have.prop', 'disabled', false)
            .should('have.text', 'Artikel generieren');
    });

    it('sollte mehrere Artikel generieren', () => {
        // Simuliere erfolgreiche AJAX-Antwort mit mehreren Artikeln
        cy.intercept('POST', '**/wp-admin/admin-ajax.php', {
            statusCode: 200,
            body: {
                success: true,
                data: [
                    {
                        id: 1,
                        title: 'Test Artikel 1',
                        category: 'Test Kategorie',
                        status: 'draft',
                        editUrl: '/wp-admin/post.php?post=1&action=edit',
                        viewUrl: '/?p=1'
                    },
                    {
                        id: 2,
                        title: 'Test Artikel 2',
                        category: 'Test Kategorie',
                        status: 'draft',
                        editUrl: '/wp-admin/post.php?post=2&action=edit',
                        viewUrl: '/?p=2'
                    },
                    {
                        id: 3,
                        title: 'Test Artikel 3',
                        category: 'Test Kategorie',
                        status: 'draft',
                        editUrl: '/wp-admin/post.php?post=3&action=edit',
                        viewUrl: '/?p=3'
                    }
                ]
            }
        }).as('generateMultiple');

        cy.get('#categorySelect').select('1');
        cy.get('#articleCount').select('3');
        cy.get('button[type="submit"]').click();

        // Warte auf die AJAX-Antwort
        cy.wait('@generateMultiple');

        cy.get('#generationResults')
            .should('be.visible')
            .and('contain', 'Artikel erfolgreich generiert');

        cy.get('#generationResults .generation-item')
            .should('have.length', 3);
    });

    it('sollte Artikel sofort veröffentlichen', () => {
        // Simuliere erfolgreiche AJAX-Antwort mit veröffentlichtem Artikel
        cy.intercept('POST', '**/wp-admin/admin-ajax.php', {
            statusCode: 200,
            body: {
                success: true,
                data: [{
                    id: 1,
                    title: 'Test Artikel',
                    category: 'Test Kategorie',
                    status: 'publish',
                    editUrl: '/wp-admin/post.php?post=1&action=edit',
                    viewUrl: '/?p=1'
                }]
            }
        }).as('generatePublished');

        cy.get('#categorySelect').select('1');
        cy.get('#publishImmediately').check();
        cy.get('button[type="submit"]').click();

        // Warte auf die AJAX-Antwort
        cy.wait('@generatePublished');

        cy.get('#generationResults')
            .should('be.visible')
            .and('contain', 'Artikel erfolgreich generiert');

        cy.get('#generationResults .generation-meta')
            .should('contain', 'Status: publish');
    });

    it('sollte benutzerdefiniertes Prompt verwenden', () => {
        // Simuliere erfolgreiche AJAX-Antwort
        cy.intercept('POST', '**/wp-admin/admin-ajax.php', {
            statusCode: 200,
            body: {
                success: true,
                data: [{
                    id: 1,
                    title: 'Test Artikel',
                    category: 'Test Kategorie',
                    status: 'draft',
                    editUrl: '/wp-admin/post.php?post=1&action=edit',
                    viewUrl: '/?p=1'
                }]
            }
        }).as('generateCustom');

        // Simuliere Editor-Seite
        cy.intercept('GET', '**/wp-admin/post.php*', {
            statusCode: 200,
            body: `
                <div class="wrap">
                    <h1 class="wp-heading-inline">Artikel bearbeiten</h1>
                    <div class="editor-post-title__block">
                        <div class="editor-post-title__input" contenteditable="true">Test Artikel</div>
                    </div>
                    <div class="block-editor-block-list__layout">
                        <p>Test Inhalt</p>
                    </div>
                </div>
            `
        }).as('editorPage');

        cy.get('#categorySelect').select('1');
        
        const customPrompt = 'Erstelle einen kurzen Test-Artikel für die Kategorie [KATEGORIE] mit maximal 100 Wörtern.';
        cy.get('#promptText')
            .clear()
            .type(customPrompt);
        
        cy.get('button[type="submit"]').click();

        // Warte auf die AJAX-Antwort
        cy.wait('@generateCustom');

        cy.get('#generationResults')
            .should('be.visible')
            .and('contain', 'Artikel erfolgreich generiert');

        // Klicke auf den Bearbeiten-Link und prüfe die Editor-Seite
        cy.get('#generationResults .generation-actions a')
            .first()
            .invoke('removeAttr', 'target')
            .click();

        // Warte auf die Editor-Seite
        cy.wait('@editorPage');

        // Prüfe die Editor-Elemente
        cy.get('.editor-post-title__input')
            .should('exist')
            .and('contain', 'Test Artikel');
        
        cy.get('.block-editor-block-list__layout')
            .should('exist')
            .and('contain', 'Test Inhalt');
    });

    it('sollte Debug-Informationen bei Fehlern anzeigen', () => {
        // Simuliere Fehler-Antwort
        cy.intercept('POST', '**/wp-admin/admin-ajax.php', {
            statusCode: 200,
            body: {
                success: false,
                data: 'Bitte geben Sie einen Prompt ein.'
            }
        }).as('generatePromptError');

        // Überwache Konsolenausgaben
        cy.window().then((win) => {
            cy.spy(win.console, 'log').as('consoleLog');
            cy.spy(win.console, 'error').as('consoleError');
        });

        // Wähle Kategorie aus
        cy.get('#categorySelect').select('1');
        
        // Lösche den Prompt (um einen Fehler zu provozieren)
        cy.get('#promptText').clear();
        
        // Klicke den Submit-Button
        cy.get('button[type="submit"]').click();

        // Warte auf die AJAX-Antwort
        cy.wait('@generatePromptError');

        // Prüfe Fehleranzeige
        cy.get('#generationResults')
            .should('contain', 'Bitte geben Sie einen Prompt ein')
            .and('have.class', 'notice-error');

        // Prüfe Debug-Ausgaben
        cy.get('@consoleLog').should('be.called');
    });

    it('sollte AJAX-Fehler korrekt behandeln', () => {
        // Überwache Konsolenausgaben
        cy.window().then((win) => {
            cy.spy(win.console, 'log').as('consoleLog');
            cy.spy(win.console, 'error').as('consoleError');
        });

        // Wähle Kategorie aus
        cy.get('#categorySelect').select('1');
        
        // Setze einen Test-Prompt
        cy.get('#promptText').clear().type('Test Prompt');
        
        // Simuliere einen Server-Fehler
        cy.intercept('POST', '**/wp-admin/admin-ajax.php', {
            statusCode: 500,
            body: 'Server Error'
        }).as('ajaxRequest');
        
        // Klicke den Submit-Button
        cy.get('button[type="submit"]').click();

        // Warte auf die AJAX-Anfrage
        cy.wait('@ajaxRequest');

        // Prüfe Fehleranzeige
        cy.get('#generationResults')
            .should('contain', 'Ein Fehler ist aufgetreten')
            .and('have.class', 'notice-error');

        // Prüfe Debug-Ausgaben
        cy.get('@consoleLog').should('be.called');
        cy.get('@consoleError').should('be.called');
    });
}); 