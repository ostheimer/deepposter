describe('DeepPoster Admin Tests', () => {
    let logs = [];
    let errors = [];

    // Percy CSS for error highlighting
    const errorHighlightCSS = `
        .notice-error {
            border: 3px solid red !important;
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.5) !important;
            background-color: #fff2f2 !important;
        }
    `;

    // Percy CSS for success states
    const successHighlightCSS = `
        .notice-success {
            border: 3px solid green !important;
            box-shadow: 0 0 10px rgba(0, 255, 0, 0.5) !important;
            background-color: #f2fff2 !important;
        }
    `;

    beforeEach(() => {
        // Reset logs and errors
        logs = [];
        errors = [];

        // Interceptor-Zähler für die Prompt-Liste
        let promptsRequestCount = 0;

        // Intercept AJAX calls for prompts list
        cy.intercept('POST', '**/admin-ajax.php', (req) => {
            if (req.body && req.body.includes('action=deepposter_get_prompts')) {
                promptsRequestCount++;
                
                // Nach dem Löschen soll die Liste leer sein
                if (promptsRequestCount > 3) {
                    req.reply({
                        statusCode: 200,
                        body: {
                            success: true,
                            data: {
                                prompts: {}
                            }
                        }
                    });
                } else {
                    req.reply({
                        statusCode: 200,
                        body: {
                            success: true,
                            data: {
                                prompts: {
                                    '1': { 
                                        id: '1', 
                                        title: 'Test Titel 1740771175427', 
                                        text: 'Test Prompt to be deleted' 
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }).as('promptsRequest');

        // Intercept AJAX calls for saving prompts
        cy.intercept('POST', '**/admin-ajax.php', (req) => {
            if (req.body && req.body.includes('action=deepposter_save_prompt')) {
                req.reply({
                    statusCode: 200,
                    body: {
                        success: true,
                        message: 'Prompt erfolgreich gespeichert',
                        data: {
                            id: '1',
                            title: 'Test Titel 1740771175427',
                            text: 'Test Prompt to be deleted'
                        }
                    }
                });
            }
        }).as('savePromptRequest');
        
        // Intercept AJAX calls for deleting prompts
        cy.intercept('POST', '**/admin-ajax.php', (req) => {
            if (req.body && req.body.includes('action=deepposter_delete_prompt')) {
                req.reply({
                    statusCode: 200,
                    body: {
                        success: true,
                        data: {
                            message: "Prompt erfolgreich gelöscht",
                            deleted_id: '1'
                        }
                    }
                });
            }
        }).as('deletePromptRequest');

        cy.visit('/wp-admin/admin.php?page=deepposter');
        cy.get('#deepposterSettingsForm').should('exist');
    });

    describe('Settings Page', () => {
        it('analyzes settings page structure', () => {
            cy.visitDeepPoster('-settings');
            
            // Debug page structure
            cy.debugPage();
            
            // Check form elements
            cy.get('form[action="options.php"]').should('exist');
            cy.get('#api_provider').should('exist');
            cy.get('#openai_key').should('exist');
            cy.get('#model_selection').should('exist');
            cy.get('#max_tokens').should('exist');
            cy.get('#temperature').should('exist');
            
            cy.percySnapshot('Settings Page Structure');
        });

        it('saves settings successfully', () => {
            cy.visitDeepPoster('-settings');
            cy.get('#api_provider').select('openai');
            cy.get('#openai_key').type('test-api-key');
            cy.get('#model_selection').select('gpt-4');
            cy.get('#max_tokens').clear().type('2000');
            cy.get('#temperature').clear().type('0.7');
            
            cy.percySnapshot('Settings Page With Data');
        });
    });

    describe('Main Dashboard Page', () => {
        beforeEach(() => {
            // Visit the main page
            cy.visitDeepPoster('');
            
            // Debug page loading
            cy.url().then(url => {
                cy.log('Current URL:', url);
            });
            
            cy.document().then(doc => {
                cy.log('Page Title:', doc.title);
                cy.log('Body Classes:', doc.body.className);
            });
            
            // Wait for page to load
            cy.get('body.wp-admin').should('exist');
            cy.get('.wrap').should('exist');
        });
        
        it('analyzes dashboard structure', () => {
            // Debug page structure
            cy.debugPage();
            
            // Check form elements with retry
            cy.get('.wrap').within(() => {
                // Wait for form to be present
                cy.get('form#deepposterSettingsForm').should('exist').then($form => {
                    cy.log('Form found:', {
                        id: $form.attr('id'),
                        action: $form.attr('action'),
                        method: $form.attr('method')
                    });
                });
                
                // Check form elements
                cy.get('#promptSelect').should('exist');
        cy.get('#promptText').should('exist');
        cy.get('#categorySelect').should('exist');
        cy.get('#articleCount').should('exist');
        cy.get('#publishImmediately').should('exist');
                cy.get('#generateButton').should('exist');
            });
            
            cy.percySnapshot('Dashboard Page Structure', {
                widths: [1280],
                percyCSS: successHighlightCSS
            });
        });

        it('loads prompt list', () => {
            // Wait for form to be present
            cy.get('form#deepposterSettingsForm').should('exist');
            
            // Wait for select to be present and select first option
            cy.get('#promptSelect').should('exist').select('1');
            
            // Wait for AJAX request to complete
            cy.wait('@promptsRequest', { timeout: 10000 });
            
            // Check prompt list with retry
            cy.get('#promptSelect option', { timeout: 10000 }).should('have.length.at.least', 1).then($options => {
                cy.log('Found options:', $options.length);
                $options.each((i, el) => {
                    cy.log('Option:', {
                        value: el.value,
                        text: el.textContent
                    });
                });
            });
            
            // Überprüfe, ob die Felder mit den Prompt-Daten gefüllt wurden
            cy.get('#promptTitle').should('have.value', 'Test Titel 1740771175427');
            cy.get('#promptText').should('have.value', 'Test Prompt to be deleted');
            
            cy.percySnapshot('Dashboard With Prompts', {
                widths: [1280],
                percyCSS: successHighlightCSS
            });
        });
        
        it('deletes prompts successfully', () => {
            cy.visitDeepPoster();
            
            // Warte auf das Formular
            cy.get('form#deepposterSettingsForm').should('exist');
            
            // Füge den Prompt-Text direkt ein
            cy.get('#promptText').should('exist')
              .clear()
              .type('Test Prompt to be deleted')
              .should('not.be.empty');
              
            // Fülle den Prompt-Titel aus
            cy.get('#promptTitle').should('exist')
              .clear()
              .type('Test Titel 1740771175427')
              .should('not.be.empty');
              
            // Speichere zuerst einen neuen Prompt
            cy.get('#savePrompt').should('exist').click();
            
            // Warte auf die Speichern-Anfrage
            cy.wait('@savePromptRequest').then((interception) => {
                expect(interception.response.body.success).to.be.true;
                expect(interception.response.body.message).to.contain('erfolgreich gespeichert');
            });
            
            // Warte auf Erfolgsmeldung oder unsere eigene Benachrichtigung
            cy.get('.notice-success, .deepposter-notice.success').should('exist').should('contain', 'erfolgreich gespeichert');
            
            // Warte auf die Aktualisierung der Dropdown-Liste
            cy.wait('@promptsRequest').then((interception) => {
                expect(interception.response.body.success).to.be.true;
                expect(interception.response.body.data.prompts['1']).to.exist;
            });
            
            // Warte darauf, dass die Option im Dropdown erscheint
            cy.get('#promptSelect option').should('have.length.at.least', 1);
            
            // Wähle den neu erstellten Prompt aus
            cy.get('#promptSelect').select('1');
            
            // Warte auf die Prompt-Details-Anfrage
            cy.wait('@promptsRequest').then((interception) => {
                expect(interception.response.body.success).to.be.true;
                expect(interception.response.body.data.prompts['1']).to.exist;
            });
            
            // Warte darauf, dass die Felder gefüllt werden
            cy.get('#promptTitle').should('have.value', 'Test Titel 1740771175427');
            cy.get('#promptText').should('have.value', 'Test Prompt to be deleted');
            
            // Warte darauf, dass der Löschen-Button sichtbar ist
            cy.get('#deletePrompt, [data-test="delete-prompt-button"]')
              .should('be.visible');
              
            // Bestätigungsdialog simulieren
            cy.on('window:confirm', () => true);
            
            // Klicke den Löschen-Button
            cy.get('#deletePrompt, [data-test="delete-prompt-button"]').click();
            
            // Warte auf die Lösch-Anfrage
            cy.wait('@deletePromptRequest').then((interception) => {
                expect(interception.response.body.success).to.be.true;
                expect(interception.response.body.data.message).to.contain('erfolgreich gelöscht');
            });
            
            // Warte auf die Aktualisierung der Dropdown-Liste
            cy.wait('@promptsRequest').then((interception) => {
                expect(interception.response.body.success).to.be.true;
                expect(Object.keys(interception.response.body.data.prompts).length).to.equal(0);
            });
            
            // Warte darauf, dass die Felder geleert werden
            cy.get('#promptTitle').should('have.value', '');
            cy.get('#promptText').should('have.value', '');
            
            // Der Löschen-Button sollte nicht mehr sichtbar sein
            cy.get('#deletePrompt, [data-test="delete-prompt-button"]').should('not.be.visible');
            
            cy.percySnapshot('After Prompt Deletion', {
                widths: [1280],
                percyCSS: successHighlightCSS
            });
        });
    });

    it('loads prompts into dropdown', () => {
        cy.get('#promptSelect').should('exist');
        cy.get('#promptSelect option').should('have.length.at.least', 1);
    });

    it('allows selecting a category', () => {
        cy.get('#categorySelect').should('exist');
        cy.get('#categorySelect option').should('have.length.at.least', 1);
    });

    it('allows setting article count', () => {
        cy.get('#articleCount').should('exist')
            .should('have.value', '1')
            .type('{selectall}3')
            .should('have.value', '3');
    });

    it('allows toggling publish status', () => {
        cy.get('#publishArticles').should('exist')
            .should('not.be.checked')
            .check()
            .should('be.checked');
    });

    it('shows generation results', () => {
        cy.get('#promptSelect').select('1');
        cy.get('#categorySelect').select('1');
        cy.get('#articleCount').type('{selectall}1');
        cy.get('#publishArticles').check();
        
        cy.intercept('POST', '**/admin-ajax.php').as('generateRequest');
        
        cy.get('#generateButton').click();
        
        cy.wait('@generateRequest').then((interception) => {
            if (interception.response.statusCode === 200 && interception.response.body.success) {
                cy.get('#generationResults').should('be.visible');
            }
        });
    });

    it('shows prompt preview when selecting a prompt', () => {
        // Warte auf das Formular
        cy.get('form#deepposterSettingsForm').should('exist');
        
        // Warte auf die Dropdown-Liste
        cy.get('#promptSelect').should('exist');
        
        // Wähle einen Prompt aus
        cy.get('#promptSelect').select('1');

        // Warte auf die AJAX-Anfrage
        cy.wait('@promptsRequest');
        
        // Überprüfe, ob der Prompt-Inhalt in der Seitenleiste angezeigt wird
        cy.get('#selectedPromptContent')
            .should('be.visible')
            .should('contain', 'Test Prompt to be deleted');
            
        cy.percySnapshot('Prompt Preview Visible', {
            widths: [1280],
            percyCSS: successHighlightCSS
        });
    });

    afterEach(() => {
        // Save debug output
        if (logs.length > 0 || errors.length > 0) {
            cy.writeFile('cypress/debug/output.log', {
                logs: logs,
                errors: errors
            });
        }
        
        // Take final snapshot with unique name based on test
        cy.percySnapshot(`Test Complete - ${Cypress.currentTest.title}`, {
            widths: [1280],
            percyCSS: `${successHighlightCSS} ${errorHighlightCSS}`
        });
    });
}); 