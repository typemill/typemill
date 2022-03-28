const fs = require("fs-extra");

/// <reference types="cypress" />
// ***********************************************************
// This example plugins/index.js can be used to load plugins
//
// You can change the location of this file or turn off loading
// the plugins file with the 'pluginsFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/plugins-guide
// ***********************************************************

// This function is called when a project is opened or re-opened (e.g. due to
// the project's config changing)

/**
 * @type {Cypress.PluginConfig}
 */
// eslint-disable-next-line no-unused-vars
module.exports = (on, config) => {
  on("task", {
    resetSetup() {
      const users = "settings/users";
      const settings = "settings/settings.yaml";
      // of course files need to exist in order to perform a delete
      if (fs.existsSync(settings) && fs.existsSync(users)) {
        fs.rmSync(users, { recursive: true, force: true });
        fs.rmSync(settings);
        fs.copyFileSync(
          "cypress/fixtures/01_setup/default-settings.yaml",
          "settings/settings.yaml"
        );
      }

      return null;
    },
    prepopulateSetup() {
      const settings = "settings";
      const settingsFixture =
        "cypress/fixtures/01_setup/prepulate_settings_seed/settings";
      // of course files need to exist in order to perform a delete
      fs.copySync(settingsFixture, settings);

      return null;
    },
  });
};
