describe("Typemill Login", function() {
    before(function() {
        cy.clearCookies();
    });
    it("redirects if visits dashboard without login", function() {
        cy.visit("/tm/content");
        cy.url().should("include", "/tm/login");
    });

    it("submits a valid form and logout", function() {
        // visits login page and adds valid input
        cy.visit("/tm/login");
        cy.url().should("include", "/tm/login");

        cy.get('input[name="username"]')
            .type("trendschau")
            .should("have.value", "trendschau")
            .and("have.attr", "required");

        cy.get('input[name="password"]')
            .type("password")
            .should("have.value", "password")
            .and("have.attr", "required");

        // can login
        cy.get("form").submit();
        cy.url().should("include", "/tm/content");
        cy.getCookie("typemill-session").should("exist");

        Cypress.Cookies.preserveOnce("typemill-session");
    });

    it("redirects if visits login form when logged in", function() {
        cy.visit("/tm/login");
        cy.url().should("include", "/tm/content");

        Cypress.Cookies.preserveOnce("typemill-session");
    });

    it("logs out", function() {
        cy.contains("Logout").click();
        cy.url().should("include", "/tm/login");
    });

    it("captcha after 1 fail", function() {
        cy.visit("/tm/login");

        // validation fails first
        cy.get('input[name="username"]').clear().type("wrong");
        cy.get('input[name="password"]').clear().type("pass");
        cy.get("form").submit();
        cy.get("#flash-message").should("contain", "wrong password or username");
        cy.get('input[name="username"]').should("have.value", "wrong");
        cy.get('input[name="password"]').should("have.value", "");
        cy.get('input[name="captcha"]').should("have.value", "");

        // captcha fails first
        cy.get('input[name="username"]').clear().type("trendschau");
        cy.get('input[name="password"]').clear().type("password");
        cy.get('input[name="captcha"]').clear().type("wrong");
        cy.get("form").submit();
        cy.get("#flash-message").should("contain", "Captcha is wrong");
        cy.get('input[name="username"]').should("have.value", "trendschau");
        cy.get('input[name="password"]').should("have.value", "");
        cy.get('input[name="captcha"]').should("have.value", "");
    });
});