describe('Typemill Initial Frontend', function() 
{
    it('has startpage with navigation', function ()
    {
      /* visit homepage */
      cy.visit('/')

      /* has startpage with headline */
      cy.get('h1').contains("Typemill")

      /* has start and setup button */
      cy.get('.cy-nav').find('a').should(($a) => {
        expect($a).to.have.length(10)
        expect($a[0].href).to.match(/welcome/)
        expect($a[1].href).to.match(/welcome\/setup/)
        expect($a[2].href).to.match(/welcome\/write-content/)
        expect($a[3].href).to.match(/welcome\/get-help/)
        expect($a[4].href).to.match(/welcome\/markdown-test/)
        expect($a[5].href).to.match(/cyanine-theme/)
        expect($a[6].href).to.match(/cyanine-theme\/landingpage/)
        expect($a[7].href).to.match(/cyanine-theme\/footer/)
        expect($a[8].href).to.match(/cyanine-theme\/colors-and-fonts/)
        expect($a[9].href).to.match(/cyanine-theme\/content-elements/)
      })      
    })

    it('has error page', function ()
    {
      cy.request({
        url: '/error',
        failOnStatusCode: false,
      })
      .then((resp) => {
          /* should return 404 not found */
          expect(resp.status).to.eq(404)
      })

      cy.visit('/error', { failOnStatusCode: false })
      cy.url().should('include','/error')

      cy.get('h1').contains('Not Found')
    })

    it('has no access to cache files', function ()
    {
      cy.request({
        url: '/cache/structure.txt',
        failOnStatusCode: false,
      })
      .then((resp) => {
          // redirect status code is 302
          expect(resp.status).to.eq(403)
      })
    })

    it('has sitemap xml', function ()
    {
      cy.request({
        url: '/cache/sitemap.xml',
      })
      .then((resp) => {
          /* should return xml-format */
          expect(resp.headers).to.have.property('content-type','application/xml')
      })
    })

    it('has no access to dashboard', function ()
    {
      cy.visit('/tm/settings')
      cy.url().should('include','/tm/login')
    })

    it('has proper markdown test page', function ()
    {
      cy.visit('/welcome/markdown-test')
      cy.url().should('include','/welcome/markdown-test')

      /* has navigation element */
      cy.get('nav').should('exist')

      /* check if toc exists */
      cy.get('.TOC').within(($toc) =>{
        /* check if a certain link in toc exists */
        cy.get('a').eq(2).should('have.attr', 'href', '/typemillTest/welcome/markdown-test#headlines')
      })
      
      /* check if corresponding anchor exists */
      cy.get('#headlines').should('exist')

      /* soft linebreaks */
      cy.get('br').should('exist')

      /* emphasis */
      cy.get('em').should('exist')

      /* strong */
      cy.get('strong').should('exist')

      /* ordered list */
      cy.get('ol').should('exist')

      /* linebreak  */
      cy.get('hr').should('exist') 
      
      /* links exists? hard to test, any idea? We need to wrap it in a div... */

      /* images */
      cy.get('img').eq(0).should('have.attr', 'alt', 'alt')
      cy.get('img').eq(0).should('have.attr', 'src', 'media/files/markdown.png')
      cy.get('figure').eq(2).should('have.id', 'myid')
        .and('have.class', 'otherclass')
      cy.get('img').eq(2).should('have.attr', 'alt', 'alt-text')
        .and('have.attr', 'title', 'my title')
        .and('have.attr', 'width', '150px')

      /* blockquote */
      cy.get('blockquote').should('exist') 
      
      /* has navigation element */
      cy.get('.notice1').should('exist')
      cy.get('.notice2').should('exist')
      cy.get('.notice3').should('exist')

      /* footnote */
      cy.get('sup').eq(0).should('have.id', 'fnref1:1')
      cy.get('sup').eq(0).within(($sup) =>{
        cy.get('a').eq(0).should('have.attr', 'href', '/typemillTest/welcome/markdown-test#fn%3A1')
          .and('have.class', 'footnote-ref')
      })

      /* abbreviation */
      cy.get('abbr').should('exist') 

      /* definition list */
      cy.get('dl').should('exist') 

      /* table */
      cy.get('table').should('exist') 

      /* code */
      cy.get('pre').should('exist') 
      cy.get('code').should('exist') 

      /* math */
      cy.get('.math').should('exist')

      /* footnote end */
      cy.get('.footnotes').within(($footnotes) => {
        cy.get('li').eq(0).should('have.id', 'fn:1')
        cy.get('a').eq(0).should('have.class', 'footnote-backref')
          .and('have.attr', 'href', '/typemillTest/welcome/markdown-test#fnref1%3A1')
          .and('have.attr', 'rev', 'footnote')
      })
    })
})