describe("Typemill System Settings", function () {
  before(function () {
    cy.loginTypemill();

    cy.visit("/tm/settings");
    cy.url().should("include", "/tm/settings");
  });

  beforeEach(function () {
    Cypress.Cookies.preserveOnce("typemill-session");
  });

  it("validates the form", function () {
    // fill out valid data
    cy.get('input[name="settings[title]"]')
      .clear()
      .type("Cypress<?")
      .should("have.value", "Cypress<?")
      .and("have.attr", "required");

    // fill out valid data
    cy.get('input[name="settings[author]"]')
      .clear()
      .type("trendschau")
      .should("have.value", "trendschau");

    // fill out copyright data
    cy.get('select[name="settings[copyright]"]')
      .select("CC-BY")
      .should("have.value", "CC-BY");

    // fill out valid data
    cy.get('input[name="settings[year]"]')
      .clear()
      .type("2017")
      .should("have.value", "2017")
      .and("have.attr", "required");

    // fill out copyright data
    cy.get('select[name="settings[language]"]')
      .select("German")
      .should("have.value", "de");

    // submit form
    cy.get("form").submit();
    cy.get("#flash-message").should("contain", "Please correct the errors");
  });

  it("changes default values", function () {
    // fill out valid data
    cy.get('input[name="settings[title]"]')
      .clear()
      .type("Cypress")
      .should("have.value", "Cypress")
      .and("have.attr", "required");

    // fill out valid data
    cy.get('input[name="settings[author]"]')
      .clear()
      .type("robot")
      .should("have.value", "robot");

    cy.get('select[name="settings[copyright]"]')
      .select("CC-BY-ND")
      .should("have.value", "CC-BY-ND");

    // fill out copyright data
    cy.get('select[name="settings[language]"]')
      .select("English")
      .should("have.value", "en");

    cy.get("form").submit();
    cy.get("#flash-message").should("contain", "Settings are stored");

    // fill out valid data
    cy.get('input[name="settings[title]"]').should("have.value", "Cypress");

    // fill out valid data
    cy.get('input[name="settings[author]"]').should("have.value", "robot");

    // fill out copyright data
    cy.get('select[name="settings[copyright]"]').should(
      "have.value",
      "CC-BY-ND"
    );

    // fill out valid data
    cy.get('input[name="settings[year]"]').should("have.value", "2017");

    // fill out copyright data
    cy.get('select[name="settings[language]"]').should("have.value", "en");
  });
});
