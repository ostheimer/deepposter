const { defineConfig } = require('cypress')

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost:8000',
    setupNodeEvents(on, config) {},
    env: {
      wpUsername: 'admin',
      wpPassword: 'admin'
    }
  },
}) 