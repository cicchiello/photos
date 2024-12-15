describe('addTag Tests', () => {
  beforeEach(() => {
    // Login first
    cy.visit('/login.php')
    cy.get('input[name="uname"]').type('joe')
    cy.get('input[name="pswd"]').type('lemon')
    cy.get('form').submit()
  })

  it('shouldn\'t be able to add tag if nothing checked', () => {
    cy.url().should('include', 'index.php')
    cy.wait(500)

    // Intercept the CouchDB request
    cy.intercept('GET', '**/photos-staging/_design/photos/_view/photo_ids*').as('photoIds')
    
    // Type 'fruit' into the tag input and click search
    cy.get('#newTag')
    .should('be.visible')
    .type('fruit')
      cy.wait(500)
      cy.get('#addNewTagButton')
      .should('be.disabled')
    })

  it('check the cherries and add a "foo" tag', () => {
    cy.url().should('include', 'index.php')
    cy.wait(500)

    // Intercept the CouchDB request
    cy.intercept('GET', '**/photos-staging/_design/photos/_view/photo_ids*').as('photoIds')
    
    // Wait for the iframe to load
    cy.get('iframe[src*="imgArrayTbl.php"]')
      .should('be.visible')
      .its('0.contentDocument.body')
      .should('not.be.empty')
      .then(cy.wrap)
      .within(() => {
        // Find the specific image and its associated checkbox
        cy.get('img[data-objid="500b6984d64439bab876480128bdb420"]')
        .parent('div')
        .find('input[type="checkbox"]')
        .should('exist')
        .check({ force: true })
        .should('be.checked')
          cy.wait(500)

      })

      // Type 'foo' into the tag input and click search
      cy.get('#newTag')
      .should('be.visible')
      .type('foo')
        cy.wait(500)
        cy.get('#addNewTagButton').click()
        cy.wait(500)
    })  
  
  it('now delete the "foo" tag', () => {
    cy.url().should('include', 'index.php')
    cy.wait(500)

    // Intercept the CouchDB request
    cy.intercept('GET', '**/photos-staging/_design/photos/_view/photo_ids*').as('photoIds')
    
    // Wait for the iframe to load
    cy.get('iframe[src*="imgArrayTbl.php"]')
      .should('be.visible')
      .its('0.contentDocument.body')
      .should('not.be.empty')
      .then(cy.wrap)
      .within(() => {
        // Click on the cherry image to open info form
        cy.get('img[data-objid="500b6984d64439bab876480128bdb420"]')
          .click()
        cy.wait(500)
      })

    cy.url().should('include', 'image_info.php')
    cy.wait(500)

    // Wait for tags to be updated and check for cherry tag
    cy.get('#detail').should('be.visible')
      .should('include.text', 'foo')
    cy.wait(500)

    // Find and click the button containing "foo" text to delete the tag
    cy.get('button')
      .contains('foo')
      .click()
    cy.wait(500)
  
    })

  })
