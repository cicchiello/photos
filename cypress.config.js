const { defineConfig } = require('cypress')

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://mediaserver/photos-staging',
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
  },
})
