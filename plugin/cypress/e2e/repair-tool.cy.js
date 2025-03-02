/**
 * Cypress-Test für das Datenbank-Reparatur-Tool
 */
describe('Datenbank-Reparatur-Tool', () => {
  beforeEach(() => {
    // Login zum WordPress-Admin
    cy.visit('/wp-login.php');
    cy.get('#user_login').type('deepposter');
    cy.get('#user_pass').type('deppposter');
    cy.get('#wp-submit').click();
    
    // Navigiere zur DeepPoster-Seite
    cy.visit('/wp-admin/admin.php?page=deepposter');
  });

  it('sollte das Hauptmenü anzeigen', () => {
    cy.get('.nav-tab-wrapper').should('be.visible');
    cy.get('.nav-tab').should('have.length.at.least', 1);
  });

  it('sollte zur Reparatur-Seite navigieren können', () => {
    // Navigiere zur Reparatur-Seite
    cy.visit('/wp-admin/admin.php?page=deepposter-repair');
    
    // Überprüfe, ob die Seite geladen wurde
    cy.get('h1').should('contain', 'DeepPoster - Datenbank reparieren');
    cy.get('#repairButton').should('be.visible');
    cy.get('#repairButton').should('contain', 'Datenbank reparieren');
  });

  it('sollte den Reparatur-Prozess starten können', () => {
    // Navigiere zur Reparatur-Seite
    cy.visit('/wp-admin/admin.php?page=deepposter-repair');
    
    // Intercepte den AJAX-Request
    cy.intercept('POST', '/wp-admin/admin-ajax.php').as('repairRequest');
    
    // Klicke auf den Reparatur-Button
    cy.get('#repairButton').click();
    
    // Überprüfe, ob der Button deaktiviert wird
    cy.get('#repairButton').should('be.disabled');
    cy.get('#repairButton').should('contain', 'Repariere...');
    
    // Warte auf den AJAX-Request
    cy.wait('@repairRequest').then((interception) => {
      // Logge die Antwort
      cy.log('AJAX-Antwort:', interception.response.body);
      
      // Überprüfe, ob die Antwort erfolgreich war
      expect(interception.request.body).to.include('action=deepposter_repair_duplicate_ids');
      expect(interception.request.body).to.include('nonce=');
    });
    
    // Überprüfe, ob die Ergebnisse angezeigt werden
    cy.get('#repairResults').should('be.visible');
    cy.get('#repairOutput').should('exist');
    
    // Überprüfe, ob der Button wieder aktiviert wird
    cy.get('#repairButton').should('not.be.disabled');
    cy.get('#repairButton').should('contain', 'Datenbank reparieren');
  });

  it('sollte nach der Reparatur zur Prompt-Auswahl navigieren können', () => {
    // Navigiere zur DeepPoster-Hauptseite
    cy.visit('/wp-admin/admin.php?page=deepposter');
    
    // Überprüfe, ob die Prompt-Auswahl angezeigt wird
    cy.get('#prompt-select').should('exist');
    
    // Intercepte den AJAX-Request für das Laden der Prompts
    cy.intercept('POST', '/wp-admin/admin-ajax.php').as('loadPromptsRequest');
    
    // Warte auf den AJAX-Request
    cy.wait('@loadPromptsRequest').then((interception) => {
      // Logge die Antwort
      cy.log('AJAX-Antwort:', interception.response.body);
      
      // Überprüfe, ob die Antwort erfolgreich war
      if (interception.request.body.includes('action=deepposter_get_prompts')) {
        expect(interception.response.statusCode).to.eq(200);
      }
    });
  });
}); 