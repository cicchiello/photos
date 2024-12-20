describe('Cherry Pick Tests', () => {
  beforeEach(() => {
    // Login first
    cy.visit('/login.php')
    cy.get('input[name="uname"]').type('joe')
    cy.get('input[name="pswd"]').type('lemon')
    cy.get('form').submit()
  })

  it('should load the main page and check an image', () => {
    cy.url().should('include', 'index.php')
    cy.wait(500)
    
    // Intercept the CouchDB request
    cy.intercept('GET', '**/photos-staging/_design/photos/_view/photo_ids*').as('photoIds')
    
    // Get the iframe
    cy.get('iframe[src*="imgArrayTbl.php"]')
      .should('be.visible')
      .its('0.contentDocument.body')
      .should('not.be.empty')
      .then(cy.wrap)
      .within(() => {
        // Wait for CouchDB request
        cy.wait('@photoIds')
        cy.wait(500)
        
        // Now look for the table
        cy.get('table').should('exist')
        cy.wait(500)
        
        // Find the specific image and its associated checkbox
        cy.get('img[data-objid="500b6984d64439bab876480128bdb420"]')
          .parent('div')
          .find('input[type="checkbox"]')
          .should('exist')
          .check({ force: true })
          .should('be.checked')
        cy.wait(500)
      })

    // Wait for tags to be updated and check for cherry tag
    cy.get('#key-area')
      .should('include.text', 'cherry')
    cy.wait(500)
  })

  it('should search for images with fruit tag', () => {
    cy.url().should('include', 'index.php')
    cy.wait(500)
    
    // Wait for the iframe to load
    cy.get('iframe[src*="imgArrayTbl.php"]')
      .should('be.visible')
      .its('0.contentDocument.body')
      .should('not.be.empty')
      .then(cy.wrap)
      .within(() => {
        cy.wait(500)
        // Type 'fruit' into the tag input and click search
        cy.get('#tagInput')
          .should('be.visible')
          .type('fruit')
        cy.wait(1500)
        
        cy.get('#findImagesButton')
          .should('be.visible')
          .click()
        cy.wait(500)
      })

    // Wait for the iframe to load
    cy.get('iframe[src*="imgArrayTbl.php"]')
      .should('be.visible')
      .its('0.contentDocument.body')
      .should('not.be.empty')
      .then(cy.wrap)
      .within(() => {
        // Check the input value
        cy.get('#tagList')
          .should('have.value', 'fruit')
          .should('have.attr', 'readonly')
        cy.wait(500)
        
        // Wait for search results and verify the specific image is present
        cy.get('img[data-objid="500b6984d64439bab876480128bdb420"]')
          .should('exist')
        cy.wait(500)
      })
  })

  it('test that 2 checks produce an intersection tag list', () => {
    cy.url().should('include', 'index.php')
    cy.wait(500)
    
    // Wait for the iframe to load
    cy.get('iframe[src*="imgArrayTbl.php"]')
      .should('be.visible')
      .its('0.contentDocument.body')
      .should('not.be.empty')
      .then(cy.wrap)
      .within(() => {
        // Type 'fruit' into the tag input and click search
        cy.get('#tagInput')
          .should('be.visible')
          .type('fruit')
        cy.wait(500)
        
        cy.get('#findImagesButton')
          .should('be.visible')
          .click()
        cy.wait(500)
      })

    // Wait for the iframe to load
    cy.get('iframe[src*="imgArrayTbl.php"]')
      .should('be.visible')
      .its('0.contentDocument.body')
      .should('not.be.empty')
      .then(cy.wrap)
      .within(() => {
        // Check the input value
        cy.get('#tagList')
          .should('have.value', 'fruit')
          .should('have.attr', 'readonly')
        cy.wait(500)
        
        // Wait for search results and verify the specific image is present
        cy.get('img[data-objid="500b6984d64439bab876480128bdb420"]')
          .should('exist')
        cy.wait(500)

        // Wait for search results and verify the specific image is present
        cy.get('img[data-objid="1ceb66fc7c47d0fc91e9e21b541ed8d7"]')
          .should('exist')
        cy.wait(500)

        // Find the specific image and its associated checkbox
        cy.get('img[data-objid="500b6984d64439bab876480128bdb420"]')
          .parent('div')
          .find('input[type="checkbox"]')
          .should('exist')
          .check({ force: true })
          .should('be.checked')
        cy.wait(500)

        // Find the specific image and its associated checkbox
        cy.get('img[data-objid="1ceb66fc7c47d0fc91e9e21b541ed8d7"]')
          .parent('div')
          .find('input[type="checkbox"]')
          .should('exist')
          .check({ force: true })
          .should('be.checked')
        cy.wait(500)
      })

    // Verify 'cherry' is NOT in the key-area
    cy.get('#key-area')
      .should('not.include.text', 'cherry')
    cy.wait(500)

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
      .should('include.text', 'cherry')
    cy.wait(500)

    // Click the return button
    cy.get('#return').click()
    cy.wait(500)

    // Verify 'cherry' is NOT in the key-area
    cy.get('#key-area')
      .should('not.include.text', 'cherry')
    cy.wait(500)

    // Verify 'fruit' is in the key-area
    cy.get('#key-area')
      .should('include.text', 'fruit')
    cy.wait(500)

    // Verify the cherry image checkbox is still checked
    cy.get('iframe[src*="imgArrayTbl.php"]')
      .should('be.visible')
      .its('0.contentDocument.body')
      .should('not.be.empty')
      .then(cy.wrap)
      .within(() => {
        cy.get('img[data-objid="500b6984d64439bab876480128bdb420"]')
          .parent('div')
          .find('input[type="checkbox"]')
          .should('be.checked')
        cy.wait(500)

        cy.get('img[data-objid="1ceb66fc7c47d0fc91e9e21b541ed8d7"]')
          .parent('div')
          .find('input[type="checkbox"]')
          .should('be.checked')
        cy.wait(500)
      })
  })
})
