const { defineConfig } = require('cypress')

module.exports = defineConfig({
  e2e: {
    setupNodeEvents(on, config) {
      on('task', {
        log(message) {
          console.log(message)
          return null
        }
      })
    },
    baseUrl: 'http://localhost:8000',
    env: {
      wpUsername: 'admin',
      wpPassword: 'admin'
    },
    supportFile: 'cypress/support/e2e.js'
  },
  video: false,
  screenshotOnRunFailure: true,
  viewportWidth: 2000,
  viewportHeight: 1320
}) 