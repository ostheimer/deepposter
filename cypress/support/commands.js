// -- This is a parent command --
Cypress.Commands.add('login', () => {
  const username = Cypress.env('wpUsername') || 'admin'
  const password = Cypress.env('wpPassword') || 'admin'
  
  cy.session([username, password], () => {
    cy.visit('/wp-admin/')
    cy.get('#user_login').clear().type(username)
    cy.get('#user_pass').clear().type(password)
    cy.get('#wp-submit').click()
  })
})

// Custom command für DeepPoster Navigation
Cypress.Commands.add('visitDeepPoster', (subpage = '') => {
  const page = subpage ? `deepposter${subpage}` : 'deepposter'
  cy.visit(`/wp-admin/admin.php?page=${page}`)
}) 