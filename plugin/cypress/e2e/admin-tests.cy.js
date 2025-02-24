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

        // Intercept AJAX calls for prompts
        cy.intercept('POST', '**/admin-ajax.php', (req) => {
            if (req.body && req.body.includes('action=deepposter_get_prompts')) {
                req.reply({
                    statusCode: 200,
                    body: {
                        success: true,
                        data: {
                            prompts: [
                                { id: 1, title: 'Test Prompt 1', content: 'This is test prompt 1' },
                                { id: 2, title: 'Test Prompt 2', content: 'This is test prompt 2' }
                            ],
                            activePrompt: 1
                        }
                    }
                });
            }
        }).as('promptsRequest');

        // Intercept AJAX calls for saving prompts
        cy.intercept('POST', '**/admin-ajax.php', (req) => {
            if (req.body && req.body.includes('action=deepposter_save_prompt')) {
                req.reply({
                    statusCode: 200,
                    body: {
                        success: true,
                        message: 'Prompt saved successfully',
                        data: {
                            id: 3,
                            title: 'New Test Prompt',
                            content: 'This is a new test prompt'
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
                            deleted_id: 1
                        }
                    }
                });
            }
        }).as('deletePromptRequest');
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
            cy.get('#promptSelect option', { timeout: 10000 }).should('have.length.at.least', 2).then($options => {
                cy.log('Found options:', $options.length);
                $options.each((i, el) => {
                    cy.log('Option:', {
                        value: el.value,
                        text: el.textContent
                    });
                });
            });
            
            cy.percySnapshot('Dashboard With Prompts', {
                widths: [1280],
                percyCSS: successHighlightCSS
            });
        });
        
        it('deletes prompts successfully', () => {
            // Warte auf das Formular
            cy.get('form#deepposterSettingsForm').should('exist');
            
            // Füge den Prompt-Text direkt ein
            cy.get('#promptText').should('exist')
              .clear()
              .type('Test Prompt to be deleted')
              .should('not.be.empty');
              
            // Speichere zuerst einen neuen Prompt
            cy.get('#savePrompt').should('exist').click();
            
            // Warte auf Erfolgsmeldung
            cy.get('.notice-success').should('contain', 'erfolgreich gespeichert');
              
            // Bestätigungsdialog simulieren
            cy.on('window:confirm', () => true);
            
            // Delete Button sollte nun sichtbar sein - klicke ihn
            cy.get('#deletePrompt').should('be.visible').click();
            
            // Erfolgsmeldung für das Löschen sollte angezeigt werden
            cy.get('.notice-success').should('contain', 'gelöscht');
            
            cy.percySnapshot('After Prompt Deletion', {
                widths: [1280],
                percyCSS: successHighlightCSS
            });
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