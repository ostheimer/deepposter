describe('DeepPoster Admin Tests', () => {
  beforeEach(() => {
    cy.login()
  })

  it('should load DeepPoster admin page', () => {
    cy.visitDeepPoster()
    cy.get('.ai-generator h1').should('contain', 'DeepPoster')
  })

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
}) 