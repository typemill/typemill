describe('Typemill Theme Settings', function() 
{
  before(function () 
  {
    cy.visit('/tm/login')
    cy.url().should('include','/tm/login')

    cy.get('input[name="username"]').type('trendschau')
    cy.get('input[name="password"]').type('password')

    cy.get('form').submit()
    cy.url().should('include','/tm/content')
    cy.getCookie('typemill-session').should('exist')

    cy.visit('/tm/themes')
    cy.url().should('include','/tm/themes')
  })

  beforeEach(function ()
  {
    Cypress.Cookies.preserveOnce('typemill-session')
  })

  it('changes default values', function()
  {

    // open the form
    cy.get('#typemill-toggle')
      .should('contain', 'Settings')
      .click()

    // fill out valid data
    cy.get('input[name="typemill[chapter]"]')
      .should('have.value', 'Chapter')
      .clear()
      .type('Kapitel')
      .should('have.value', 'Kapitel')

    // fill out valid data
    cy.get('input[name="typemill[start]"]')
      .should('have.value', 'Start')
      .clear()
      .type('Run')
      .should('have.value', 'Run')

    // fill out valid data
    cy.get('input[name="typemill[chapnum]"]')
      .should('not.be.checked')
      .and('not.be.visible')
      .check({ force: true })
      .should('be.checked')

    // fill out valid data
    cy.get('input[name="typemill[authorPosition][top]"]')
      .should('not.be.checked')
      .and('not.be.visible')
      .check({ force: true })
      .should('be.checked')

    // fill out valid data
    cy.get('input[name="typemill[authorIntro]"]')
      .should('have.value', 'Author')
      .clear()
      .type('Writer')
      .should('have.value', 'Writer')

    // fill out valid data
    cy.get('input[name="typemill[modifiedPosition][bottom]"]')
      .should('not.be.checked')
      .and('not.be.visible')
      .check({ force: true })
      .should('be.checked')

    // fill out valid data
    cy.get('input[name="typemill[modifiedText]"]')
      .should('have.value', 'Last updated')
      .clear()
      .type('Final update')
      .should('have.value', 'Final update')

    cy.get('select[name="typemill[modifiedFormat]"]')
      .should('have.value', 'd.m.Y')
      .select('m/d/Y')
      .should('have.value', 'm/d/Y')

    cy.get('input[name="typemill[socialPosition][bottom]"]')
      .should('not.be.checked')
      .and('not.be.visible')
      .check({ force: true })
      .should('be.checked')

    cy.get('input[name="typemill[socialButtons][facebook]"]')
      .should('not.be.checked')
      .and('not.be.visible')
      .check({ force: true })
      .should('be.checked')

    cy.get('input[name="typemill[socialButtons][twitter]"]')
      .should('not.be.checked')
      .and('not.be.visible')
      .check({ force: true })
      .should('be.checked')

    cy.get('input[name="typemill[socialButtons][xing]"]')
      .should('not.be.checked')
      .and('not.be.visible')
      .check({ force: true })
      .should('be.checked')

    cy.get('input[name="typemill[gitPosition][top]"]')
      .should('not.be.checked')
      .and('not.be.visible')
      .check({ force: true })
      .should('be.checked')

    cy.get('input[name="typemill[gitlink]"]')
      .clear()
      .type('https://github.com/typemill/docs')
      .should('have.value', 'https://github.com/typemill/docs')


    cy.get('#theme-typemill').submit()
        cy.get('#flash-message').should('contain', 'Settings are stored')


    // fill out valid data
    cy.get('input[name="typemill[chapter]"]')
      .should('have.value', 'Kapitel')

    // fill out valid data
    cy.get('input[name="typemill[start]"]')
      .should('have.value', 'Run')

    // fill out valid data
    cy.get('input[name="typemill[chapnum]"]')
      .should('be.checked')

    // fill out valid data
    cy.get('input[name="typemill[authorPosition][top]"]')
      .should('be.checked')

    // fill out valid data
    cy.get('input[name="typemill[authorIntro]"]')
      .should('have.value', 'Writer')

    // fill out valid data
    cy.get('input[name="typemill[modifiedPosition][bottom]"]')
      .should('be.checked')

    // fill out valid data
    cy.get('input[name="typemill[modifiedText]"]')
      .should('have.value', 'Final update')

    cy.get('select[name="typemill[modifiedFormat]"]')
      .should('have.value', 'm/d/Y')

    cy.get('input[name="typemill[socialPosition][bottom]"]')
      .should('be.checked')

    cy.get('input[name="typemill[socialButtons][facebook]"]')
      .should('be.checked')

    cy.get('input[name="typemill[socialButtons][twitter]"]')
      .should('be.checked')

    cy.get('input[name="typemill[socialButtons][xing]"]')
      .should('be.checked')

    cy.get('input[name="typemill[gitPosition][top]"]')
      .should('be.checked')

    cy.get('input[name="typemill[gitlink]"]')
      .should('have.value', 'https://github.com/typemill/docs')
  })

  it('validates input', function()
  {

    // open the form
    cy.get('#typemill-toggle')
      .should('contain', 'Settings')
      .click()

    // fill out valid data
    cy.get('input[name="typemill[chapter]"]')
      .should('have.value', 'Kapitel')
      .clear()
      .type('Kapitel<?')
      .should('have.value', 'Kapitel<?')

      // submit form
    cy.get('#theme-typemill').submit()
    
    cy.get('#flash-message').should('contain', 'Please correct the errors')

  })
})