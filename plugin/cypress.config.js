const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost:8000',
    supportFile: 'cypress/support/e2e.js',
    specPattern: 'cypress/e2e/**/*.cy.{js,jsx,ts,tsx}',
    screenshotsFolder: 'cypress/screenshots',
    videosFolder: 'cypress/videos',
    viewportWidth: 1280,
    viewportHeight: 720,
    defaultCommandTimeout: 10000,
    pageLoadTimeout: 30000,
    requestTimeout: 10000,
    responseTimeout: 10000,
    video: true,
    screenshotOnRunFailure: true,
    chromeWebSecurity: false,
    env: {
      wpUsername: 'deepposter',
      wpPassword: 'deepposter'
    },
    setupNodeEvents(on, config) {
      on('before:browser:launch', (browser = {}, launchOptions) => {
        console.log('Launching browser with options:', launchOptions);
        if (browser.name === 'chrome' || browser.name === 'electron') {
          launchOptions.args = launchOptions.args || [];
          launchOptions.args.push('--disable-dev-shm-usage');
          launchOptions.args.push('--disable-gpu');
          launchOptions.args.push('--no-sandbox');
          launchOptions.args.push('--enable-features=SameSiteByDefaultCookies,CookiesWithoutSameSiteMustBeSecure');
        }
        return launchOptions;
      });
      
      on('task', {
        log(message) {
          console.log(message);
          return null;
        },
        clearCookies() {
          console.log('Clearing cookies');
          return null;
        },
        getCookies() {
          console.log('Getting cookies');
          return null;
        }
      });
      
      return config;
    },
  },
}); 