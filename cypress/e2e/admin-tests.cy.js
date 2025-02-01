describe('DeepPoster Admin Tests', () => {
  const logs = {
    console: [],
    errors: [],
    warnings: []
  };

  beforeEach(() => {
    // Reset logs
    logs.console = [];
    logs.errors = [];
    logs.warnings = [];

    // Setup console logging
    cy.window().then((win) => {
      win.console.log = (msg) => {
        logs.console.push(msg);
      };
      win.console.error = (msg) => {
        logs.errors.push(msg);
      };
      win.console.warn = (msg) => {
        logs.warnings.push(msg);
      };
    });

    // Ignoriere unbehandelte Ausnahmen für alle Tests
    Cypress.on('uncaught:exception', (err, runnable) => {
      return false;
    });

    cy.log('Starte Test-Vorbereitung');
    cy.login();
    cy.url().then(url => {
      cy.log(`Aktuelle URL nach Login: ${url}`);
    });
    
    cy.visit('/wp-admin/admin.php?page=deepposter').then(() => {
      cy.log('Navigiere zur DeepPoster Admin-Seite');
      cy.url().then(url => {
        cy.log(`Aktuelle URL nach Navigation: ${url}`);
      });
    });
  });

  it('should load DeepPoster admin page', () => {
    cy.visitDeepPoster();
    cy.wait(2000);
    cy.debugPage();
    cy.get('.wrap h2').should('contain', 'DeepPoster');
  });

  it('should show settings page', () => {
    cy.visitDeepPoster('-settings')
    cy.get('.wrap h2').should('contain', 'Einstellungen')
  })

  it('should show error messages', () => {
    cy.visitDeepPoster()
    cy.get('.error').should('not.exist')
    cy.get('.notice-error').should('not.exist')
  })

  it('should show system status page', () => {
    cy.visitDeepPoster('-status')
    cy.get('.wrap h2').should('contain', 'System Status')
    
    cy.get('.status-section').first().within(() => {
      cy.get('h3').should('contain', 'Plugin Information')
      cy.get('.status-list').should('exist')
      cy.get('.status-list li').should('have.length.at.least', 2)
      cy.contains('Version:').should('exist')
      cy.contains('Debug Mode:').should('exist')
    })

    cy.get('.status-section').eq(1).within(() => {
      cy.get('h3').should('contain', 'WordPress Umgebung')
      cy.get('.status-list').should('exist')
      cy.get('.status-list li').should('have.length', 3)
      cy.contains('WordPress Version:').should('exist')
      cy.contains('PHP Version:').should('exist')
      cy.contains('MySQL Version:').should('exist')
    })

    cy.get('.status-section').eq(2).within(() => {
      cy.get('h3').should('contain', 'Debug Information')
      cy.get('pre').should('exist')
    })
  })

  it('should test model loading and selection', () => {
    // Lösche alte Konsolenausgaben
    cy.clearConsoleLogs()
    
    cy.login()
    
    // Navigiere zu den Einstellungen
    cy.visit('/wp-admin/admin.php?page=deepposter-settings')
    
    // Warte auf das Laden der Modelle und protokolliere die Konsolenausgaben
    cy.get('#loading-models', { timeout: 10000 }).should('be.visible')
    cy.wait(2000)
    
    // Speichere die Konsolenausgaben
    cy.getConsoleLogs().then((logs) => {
      // Formatiere die Logs für bessere Lesbarkeit
      const formattedLogs = logs.map(log => ({
        time: log.timestamp,
        type: log.type,
        message: log.message
      }));
      
      cy.writeFile('cypress/logs/model-loading.json', 
        JSON.stringify(formattedLogs, null, 2)
      );
      
      // Protokolliere die Logs auch in der Cypress-Konsole
      cy.log('Konsolenausgaben während des Ladens:');
      formattedLogs.forEach(log => {
        cy.log(`${log.type}: ${log.message}`);
      });
    })
    
    // Mache einen Screenshot der Ladeansicht
    cy.screenshot('model-loading', {
      capture: 'viewport',
      blackout: ['input[type="password"]']
    })
    
    // Warte auf die Modellauswahl
    cy.get('#model_selection', { timeout: 10000 })
      .should('be.visible')
      .then(($select) => {
        // Protokolliere die verfügbaren Optionen
        const options = Array.from($select.find('option')).map(opt => ({
          value: opt.value,
          text: opt.textContent
        }));
        
        cy.writeFile('cypress/logs/available-models.json', 
          JSON.stringify(options, null, 2)
        );
        
        // Protokolliere die Optionen in der Cypress-Konsole
        cy.log('Verfügbare Modelle:');
        options.forEach(opt => {
          cy.log(`${opt.text} (${opt.value})`);
        });
        
        // Versuche "4" auszuwählen, wenn verfügbar
        const gpt4Option = options.find(opt => opt.value.includes('4'));
        if (gpt4Option) {
          cy.wrap($select).select(gpt4Option.value);
          cy.log(`Modell "${gpt4Option.text}" ausgewählt`);
        } else {
          cy.log('Kein GPT-4 Modell gefunden');
        }
      })
    
    // Warte einen Moment und mache einen finalen Screenshot
    cy.wait(1000)
    cy.screenshot('model-selection-final', {
      capture: 'viewport',
      blackout: ['input[type="password"]']
    })
  })

  it('lädt die Einstellungsseite und überprüft Console Logs', () => {
    const logs = [];
    const errors = [];
    const warnings = [];

    // Überschreibe console-Methoden vor dem Seitenbesuch
    cy.visit('/wp-admin/admin.php?page=deepposter-settings', {
      onBeforeLoad(win) {
        win.console.log = (...args) => {
          logs.push({
            timestamp: new Date().toISOString(),
            message: args.map(arg => 
              typeof arg === 'object' ? JSON.stringify(arg) : String(arg)
            ).join(' ')
          });
        };
        win.console.error = (...args) => {
          errors.push({
            timestamp: new Date().toISOString(),
            message: args.map(arg => 
              typeof arg === 'object' ? JSON.stringify(arg) : String(arg)
            ).join(' ')
          });
        };
        win.console.warn = (...args) => {
          warnings.push({
            timestamp: new Date().toISOString(),
            message: args.map(arg => 
              typeof arg === 'object' ? JSON.stringify(arg) : String(arg)
            ).join(' ')
          });
        };
      }
    });
    
    // Warte auf das Laden der Modelle
    cy.get('#loading-models', { timeout: 10000 }).should('be.visible');
    
    // Warte auf AJAX-Anfragen
    cy.wait(2000);
    
    // Speichere die gesammelten Logs
    cy.wrap(null).then(() => {
      cy.writeFile('cypress/logs/all_console_output.json', {
        logs,
        errors,
        warnings,
        timestamp: new Date().toISOString()
      });
    });

    // Mache Screenshot
    cy.screenshot('model-selection-with-logs');
  });

  it('überprüft die Modellauswahl und deren Standardwert', () => {
    const logs = [];
    
    // Überschreibe console.log vor dem Seitenbesuch
    cy.visit('/wp-admin/admin.php?page=deepposter-settings', {
      onBeforeLoad(win) {
        win.console.log = (...args) => {
          logs.push({
            timestamp: new Date().toISOString(),
            message: args.map(arg => 
              typeof arg === 'object' ? JSON.stringify(arg) : String(arg)
            ).join(' ')
          });
        };
      }
    });
    
    // Warte auf das Laden der Modelle
    cy.get('#loading-models', { timeout: 10000 }).should('be.visible');
    
    // Überprüfe ob die Modellauswahl sichtbar ist
    cy.get('#model_selection').should('be.visible');
    
    // Überprüfe ob gpt-4o als Standard ausgewählt ist
    cy.get('#model_selection').should('have.value', 'gpt-4o');
    
    // Speichere die Logs
    cy.wrap(null).then(() => {
      cy.writeFile('cypress/logs/model_selection_detailed.json', {
        logs,
        timestamp: new Date().toISOString()
      });
    });
  });

  it('sollte die Struktur der Einstellungsseite analysieren', () => {
    cy.log('Starte Test: Struktur der Einstellungsseite');
    cy.visit('/wp-admin/admin.php?page=deepposter').then(() => {
      cy.log('Seite besucht');
    });
    cy.wait(2000);
    cy.debugPage();
    cy.get('#aiGeneratorForm').should('be.visible').then(() => {
      cy.log('Formular gefunden');
    });
    cy.get('#promptText').should('be.visible');
    cy.get('#categorySelect').should('be.visible');
    cy.get('#articleCount').should('be.visible');
    cy.get('#publishImmediately').should('be.visible');
  });

  it('sollte einen Artikel mit Standard-Prompt generieren', () => {
    cy.log('Starte Test: Artikel generieren');
    cy.visit('/wp-admin/admin.php?page=deepposter').then(() => {
        cy.log('Seite besucht');
    });
    cy.wait(2000);
    cy.debugPage();
    
    // Überprüfe und aktualisiere das Prompt
    cy.get('#promptText').should('be.visible').then(($textarea) => {
        const promptText = $textarea.val();
        cy.log('Aktuelles Prompt:', promptText);
        // Stelle sicher, dass das Prompt nicht leer ist
        expect(promptText).to.not.be.empty;
    });
    
    // Wähle Kategorie und Einstellungen
    cy.get('#categorySelect').select('1');
    cy.get('#articleCount').select('1');
    cy.get('#publishImmediately').check();
    cy.get('button[type="submit"]').click();
    
    // Warte auf Erfolgsmeldung im generation-results Container
    cy.get('#generationResults', { timeout: 30000 }).within(() => {
        cy.get('.generation-item', { timeout: 30000 }).should('be.visible');
        cy.get('.generation-item h3').should('exist');
        cy.get('.generation-meta').should('contain', 'Status:');
        cy.get('.generation-actions').should('exist');
    });

    // Überprüfe, ob der Artikel in der WordPress-Admin-Liste erscheint
    cy.visit('/wp-admin/edit.php');
    cy.get('#the-list').should('not.be.empty');
    cy.get('#the-list tr').first().within(() => {
        cy.get('.column-title').should('exist');
        cy.get('.column-date').should('exist');
    });
  });

  it('sollte 30000 Tokens akzeptieren und speichern', () => {
    // Besuche direkt die Einstellungsseite
    cy.visit('/wp-admin/admin.php?page=deepposter-settings');
    
    // Warte bis die Seite geladen ist
    cy.contains('h2', 'AI Provider Einstellungen').should('be.visible');
    
    // Warte bis das Eingabefeld sichtbar ist und interagierbar
    cy.get('#max_tokens')
        .should('be.visible')
        .should('be.enabled')
        .as('tokenInput');
    
    // Lösche den bestehenden Wert und gebe 30000 ein
    cy.get('@tokenInput')
        .clear()
        .type('30000')
        .blur();
    
    // Prüfe den Wert
    cy.get('@tokenInput').should('have.value', '30000');
    
    // Prüfe, ob keine Fehlermeldung erscheint
    cy.get('.error').should('not.exist');
    cy.get('.notice-error').should('not.exist');
    
    // Klicke auf "Änderungen speichern"
    cy.get('input[type="submit"]').click();
    
    // Warte einen Moment
    cy.wait(1000);
    
    // Lade die Seite neu
    cy.reload();
    
    // Warte bis die Seite nach dem Neuladen geladen ist
    cy.contains('h2', 'AI Provider Einstellungen').should('be.visible');
    
    // Prüfe, ob der Wert 30000 noch da ist
    cy.get('#max_tokens').should('have.value', '30000');
    
    // Mache einen Screenshot des Ergebnisses
    cy.screenshot('token-input-30000', {
        capture: 'viewport',
        blackout: ['input[type="password"]']
    });
  });

  it('sollte große Token-Werte validieren', () => {
    // Besuche direkt die Einstellungsseite
    cy.visit('/wp-admin/admin.php?page=deepposter-settings');
    
    // Warte bis die Seite geladen ist
    cy.contains('h2', 'AI Provider Einstellungen').should('be.visible');
    
    // Teste verschiedene Token-Werte
    const testCases = [
        { value: '30000', expected: '30000' },
        { value: '32000', expected: '32000' },
        { value: '16000', expected: '16000' }
    ];
    
    testCases.forEach(({ value, expected }) => {
        cy.get('#max_tokens')
            .should('be.visible')
            .should('be.enabled')
            .clear()
            .type(value)
            .blur();
        
        // Prüfe den Wert
        cy.get('#max_tokens').should('have.value', expected);
        
        // Prüfe auf Abwesenheit von Fehlermeldungen
        cy.get('.error').should('not.exist');
        cy.get('.notice-error').should('not.exist');
        
        // Speichere die Änderungen
        cy.get('input[type="submit"]').click();
        
        // Warte einen Moment
        cy.wait(1000);
        
        // Lade die Seite neu
        cy.reload();
        
        // Warte bis die Seite nach dem Neuladen geladen ist
        cy.contains('h2', 'AI Provider Einstellungen').should('be.visible');
        
        // Prüfe den Wert
        cy.get('#max_tokens').should('have.value', expected);
        
        // Logge den Testfall
        cy.log(`Testfall "${value}" erfolgreich`);
    });
  });
}) 