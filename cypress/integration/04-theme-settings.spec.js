describe("Typemill Theme Settings", function() {
    before(function() {
        cy.clearCookies();
    });

    beforeEach(function() {
        cy.visit("/tm/login");
        cy.url().should("include", "/tm/login");

        cy.get('input[name="username"]').type("trendschau");
        cy.get('input[name="password"]').type("password");

        cy.get("form").submit();
        cy.url().should("include", "/tm/content");
        cy.getCookie("typemill-session").should("exist");

        cy.visit("/tm/themes");
        cy.url().should("include", "/tm/themes");
    });

    afterEach(function() {
        cy.visit("/tm/logout");
    });

    it("changes default values", function() {
        // open the form
        cy.get("#cyanine-toggle").should("contain", "Settings").click();

        // fill out valid data
        cy.get('input[name="cyanine[introButtonLink]"]')
            .clear()
            .type("https://typemill.net")
            .should("have.value", "https://typemill.net");

        // fill out valid data
        cy.get('input[name="cyanine[introButtonLabel]"]')
            .clear()
            .type("Typemill")
            .should("have.value", "Typemill");

        // fill out valid data
        cy.get('input[name="cyanine[chapnum]"]')
            .should("not.be.checked")
            .and("not.be.visible")
            .check({ force: true })
            .should("be.checked");

        // fill out valid data
        cy.get('input[name="cyanine[authorPosition][top]"]')
            .should("not.be.checked")
            .and("not.be.visible")
            .check({ force: true })
            .should("be.checked");

        // fill out valid data
        cy.get('input[name="cyanine[authorIntro]"]')
            .clear()
            .type("Writer")
            .should("have.value", "Writer");

        // fill out valid data
        cy.get('input[name="cyanine[datePosition][bottom]"]')
            .should("not.be.checked")
            .and("not.be.visible")
            .check({ force: true })
            .should("be.checked");

        // fill out valid data
        cy.get('input[name="cyanine[dateIntro]"]')
            .clear()
            .type("Final update")
            .should("have.value", "Final update");

        cy.get('select[name="cyanine[dateFormat]"]')
            .should("have.value", "m/d/Y")
            .select("m/d/Y")
            .should("have.value", "m/d/Y");

        cy.get('input[name="cyanine[gitPosition][top]"]')
            .should("not.be.checked")
            .and("not.be.visible")
            .check({ force: true })
            .should("be.checked");

        cy.get('input[name="cyanine[gitLink]"]')
            .clear()
            .type("https://github.com/typemill/docs")
            .should("have.value", "https://github.com/typemill/docs");

        cy.get("#theme-cyanine").submit();
        cy.get("#flash-message").should("contain", "Settings are stored");

        // fill out valid data
        cy.get('input[name="cyanine[introButtonLink]"]').should(
            "have.value",
            "https://typemill.net"
        );

        // fill out valid data
        cy.get('input[name="cyanine[introButtonLabel]"]').should(
            "have.value",
            "Typemill"
        );

        // fill out valid data
        cy.get('input[name="cyanine[chapnum]"]').should("be.checked");

        // fill out valid data
        cy.get('input[name="cyanine[authorPosition][top]"]').should("be.checked");

        // fill out valid data
        cy.get('input[name="cyanine[authorIntro]"]').should("have.value", "Writer");

        // fill out valid data
        cy.get('input[name="cyanine[datePosition][bottom]"]').should("be.checked");

        // fill out valid data
        cy.get('input[name="cyanine[dateIntro]"]').should(
            "have.value",
            "Final update"
        );

        cy.get('select[name="cyanine[dateFormat]"]').should("have.value", "m/d/Y");

        cy.get('input[name="cyanine[gitPosition][top]"]').should("be.checked");

        cy.get('input[name="cyanine[gitLink]"]').should(
            "have.value",
            "https://github.com/typemill/docs"
        );
    });

    it("validates input", function() {
        // open the form
        cy.get("#cyanine-toggle").should("contain", "Settings").click();

        // fill out invalid data
        cy.get('input[name="cyanine[introButtonLabel]"]')
            .should("have.value", "Typemill")
            .clear()
            .type("Kapitel<?")
            .should("have.value", "Kapitel<?");

        // submit form
        cy.get("#theme-cyanine").submit();

        cy.get("#flash-message").should("contain", "Please correct the errors");
    });
});