// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })

Cypress.Commands.add("loginTypemill", () => {
  cy.visit("/tm/login");
  cy.url().should("include", "/tm/login");

  cy.get('input[name="username"]').type("trendschau");
  cy.get('input[name="password"]').type("password");

  cy.get("form").submit();
  cy.url().should("include", "/tm/content");
  cy.getCookie("typemill-session").should("exist");
});

Cypress.Commands.add("logoutTypemill", () => {
  cy.visit("/tm/logout");
});
