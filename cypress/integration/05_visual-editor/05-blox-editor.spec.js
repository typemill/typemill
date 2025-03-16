describe("Blox Editor", function () {
  before(function () {
    cy.loginTypemill();
    cy.visit("/tm/content/visual");
    cy.url().should("include", "/tm/content/visual");
  });

  beforeEach(function () {
    Cypress.Cookies.preserveOnce("typemill-session");
  });

  it("creates new page", function () {
    // click on add element
    cy.get(".addNaviItem > a").eq(0).click();

    /* Check dublicates cannot be made */

    /* Check new page can be created */
    cy.get(".addNaviForm").within((naviform) => {
      /* add Testpage into input */
      cy.get("input").clear().type("Testpage").should("have.value", "Testpage");

      cy.get(".b-left").click();
    });

    /* get Navilist */
    cy.get(".navi-list")
      .should("contain", "Testpage")
      .eq(2)
      .find("a")
      .should((a) => {
        expect(a).to.have.length(6);
        expect(a[5].href).to.include("/welcome/testpage");
      });
  });

  it("edits default content", function () {
    cy.visit("/tm/content/visual/welcome/testpage");
    cy.url().should("include", "/tm/content/visual/welcome/testpage");

    cy.get("#blox").within((blox) => {
      /* Change Title */
      cy.get("#blox-0").click();
      cy.get("input").clear().type("This is my Testpage");

      cy.get(".edit").click();
      cy.get("#blox-0").should("contain", "This is my Testpage");

      /* Change Text */
      cy.get("#blox-1").click();
      cy.get("textarea")
        .clear()
        .type("This is the new paragraph for the first line with some text.");

      cy.get(".edit").click();
      cy.get("#blox-1").should("contain", "new paragraph");
    });
  });

  it("edits paragraph", function () {
    cy.get("#blox").within((blox) => {
      /* Get Format Bar */
      cy.get(".format-bar").within((formats) => {
        /* Edit Table */
        cy.get("button").eq(0).click();
        cy.get("textarea").type("This is a second paragraph.");

        /* save table */
        cy.get(".edit").click();
        cy.get(".cancel").click();
      });

      cy.get("#blox-2").should("contain", "second paragraph");
    });
  });

  it("edits headline", function () {
    cy.get("#blox").within((blox) => {
      /* Get Format Bar */
      cy.get(".format-bar").within((formats) => {
        /* Edit Table */
        cy.get("button").eq(1).click();
        cy.get("input").type("Second Level Headline");

        /* save block */
        cy.get(".edit").click();

        /* close new standard textarea */
        cy.get(".cancel").click();
      });

      cy.get("#blox-3").should("contain", "Second Level Headline");
    });
  });

  it("edits unordered list", function () {
    cy.get("#blox").within((blox) => {
      /* Get Format Bar */
      cy.get(".format-bar").within((formats) => {
        /* Edit Table */
        cy.get("button").eq(2).click();
        cy.get("textarea").type("first list item{enter}second list item");

        /* save block */
        cy.get(".edit").click();

        /* close new standard textarea */
        cy.get(".cancel").click();
      });

      cy.get("#blox-4").within((block) => {
        cy.get("li").should((lis) => {
          expect(lis).to.have.length(2);
          expect(lis.eq(0)).to.contain("first list item");
        });
      });
    });
  });

  it("edits ordered list", function () {
    cy.get("#blox").within((blox) => {
      /* Get Format Bar */
      cy.get(".format-bar").within((formats) => {
        /* Edit Table */
        cy.get("button").eq(3).click();
        cy.get("textarea").type("first ordered item{enter}second ordered item");

        /* save block */
        cy.get(".edit").click();

        /* close new standard textarea */
        cy.get(".cancel").click();
      });

      cy.get("#blox-5").within((block) => {
        cy.get("li").should((lis) => {
          expect(lis).to.have.length(2);
          expect(lis.eq(0)).to.contain("first ordered item");
        });
      });
    });
  });

  it("edits table", function () {
    cy.get("#blox").within((blox) => {
      /* Get Format Bar */
      cy.get(".format-bar").within((formats) => {
        /* Edit Table */
        cy.get("button").eq(4).click();
        cy.get("table").within((table) => {
          /* edit table head */
          cy.get("tr")
            .eq(1)
            .within((row) => {
              cy.get("th").eq(1).click().clear().type("first Headline");
              cy.get("th").eq(2).click().clear().type("Second Headline");
            });

          /* edit first content row */
          cy.get("tr")
            .eq(2)
            .within((row) => {
              cy.get("td").eq(1).click().clear().type("Some");
              cy.get("td").eq(2).click().clear().type("More");
            });

          /* edit second content row */
          cy.get("tr")
            .eq(3)
            .within((row) => {
              cy.get("td").eq(1).click().clear().type("Beautiful");
              cy.get("td").eq(2).click().clear().type("Content");
            });

          /* add new column on the right */
          cy.get("tr")
            .eq(0)
            .within((row) => {
              cy.get("td").eq(2).click();
              cy.get(".actionline").eq(0).click();
            });
        });

        cy.get("table").within((table) => {
          /* edit second new column head */
          cy.get("tr")
            .eq(1)
            .within((row) => {
              cy.get("th").eq(3).click().clear().type("New Head");
            });

          /* edit second new column head */
          cy.get("tr")
            .eq(2)
            .within((row) => {
              cy.get("td").eq(3).click().clear().type("With");
            });

          /* edit second new column head */
          cy.get("tr")
            .eq(3)
            .within((row) => {
              cy.get("td").eq(3).click().clear().type("new Content");
            });
        });

        /* save table */
        cy.get(".edit").click();
      });

      cy.get("#blox-6").should("contain", "Beautiful").click();

      cy.get(".editactive").within((activeblock) => {
        cy.get(".component").should("contain", "Beautiful");
      });
    });
  });

  it("Publishes new page", function () {
    cy.visit("/tm/content/visual/welcome/testpage");
    cy.url().should("include", "/tm/content/visual/welcome/testpage");

    cy.get("#publish").click().wait(500);

    cy.visit("/welcome/testpage");
    cy.url().should("include", "/welcome/testpage");

    cy.get(".cy-nav").should("contain", "Testpage");
  });

  it("has sitemap xml", function () {
    cy.request({
      url: "/cache/sitemap.xml",
    }).then((resp) => {
      /* should return xml-format */
      expect(resp.headers).to.have.property("content-type", "application/xml");
    });
  });

  it("Deletes new page", function () {
    cy.visit("/tm/content/visual/welcome/testpage");
    cy.url().should("include", "/tm/content/visual/welcome/testpage");

    cy.get(".danger").click();

    cy.get("#modalWindow").within((modal) => {
      cy.get("button").click();
    });

    cy.visit("/tm/content/visual/welcome");
    cy.get(".navi-list")
      .not("contain", "Testpage")
      .eq(2)
      .find("a")
      .should((a) => {
        expect(a).to.have.length(5);
      });
  });
});
