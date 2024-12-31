describe('Cherry Pick Tests', () => {
  beforeEach(() => {
    // Set up intercept before navigation
    cy.intercept('GET', '**/_design/photos/_view/photo_ids*').as('photoIds')
    
    // Login first
    cy.visit('/login.php')
    cy.get('input[name="uname"]').type('joe')
    cy.get('input[name="pswd"]').type('lemon')
    cy.get('form').submit()
  })

  it('should select and deselect all images using select-all checkbox', () => {
    cy.url().should('include', 'index.php')
    cy.wait(500)
    
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
        
        // Check select-all checkbox
        cy.get('#selectAllCheckbox')
          .should('exist')
          .check({ force: true })
          .should('be.checked')
        cy.wait(500)

        // Verify all checkboxes are checked
        cy.get('input[type="checkbox"]').each(($checkbox) => {
          if ($checkbox.attr('id') !== 'selectAllCheckbox') {
            const $img = $checkbox.parent().next('img')
            if ($img && $img.attr('data-objid') !== 'null') {
              cy.wrap($checkbox).should('be.checked')
            }
          }
        })

        // Uncheck select-all checkbox
        cy.get('#selectAllCheckbox')
          .uncheck({ force: true })
          .should('not.be.checked')
        cy.wait(500)

        // Verify all checkboxes are unchecked
        cy.get('input[type="checkbox"]').each(($checkbox) => {
          if ($checkbox.attr('id') !== 'selectAllCheckbox') {
            const $img = $checkbox.parent().next('img')
            if ($img && $img.attr('data-objid') !== 'null') {
              cy.wrap($checkbox).should('not.be.checked')
            }
          }
        })
      })
  })

  it('should load the main page and check an image', () => {
    cy.url().should('include', 'index.php')
    cy.wait(500)
    
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
    cy.wait(1500)

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

  it('should preserve state when downloading an image', () => {
    cy.visit('/index.php')
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
          .type('cherry')
        cy.wait(500)
        
        cy.get('#findImagesButton')
          .should('be.visible')
          .click()
        cy.wait(1500)
      })

    // Check an image
    cy.get('iframe[src*="imgArrayTbl.php"]')
      .should('be.visible')
      .its('0.contentDocument.body')
      .should('not.be.empty')
      .then(cy.wrap)
      .within(() => {
        cy.get('img[data-objid="500b6984d64439bab876480128bdb420"]')
          .parent('div')
          .find('input[type="checkbox"]')
          .check({ force: true })
        cy.wait(500)
      })

    // Click on the image to go to image_info
    cy.get('iframe[src*="imgArrayTbl.php"]')
      .should('be.visible')
      .its('0.contentDocument.body')
      .should('not.be.empty')
      .then(cy.wrap)
      .within(() => {
        cy.get('img[data-objid="500b6984d64439bab876480128bdb420"]')
          .click()
      })

    // Verify URL contains our state
    cy.url().should('include', 'tags=cherry')
    cy.url().should('include', 'checked=500b6984d64439bab876480128bdb420')

    // Click download
    cy.get('#return').should('be.visible') // Wait for page load
    cy.get('img[src="img/download.png"]').click()

    cy.wait(500)
    
    // After download completes, verify we're back on image_info with state preserved
    cy.url().should('include', 'image_info.php')
    cy.url().should('include', 'tags=cherry')
    cy.url().should('include', 'checked=500b6984d64439bab876480128bdb420')
  })

  it('should search for images with produce tag, then exclude cherry', () => {
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
        // Type 'produce' into the tag input and click search
        cy.get('#tagInput')
          .should('be.visible')
          .type('produce')
        cy.wait(1500)
        
        cy.get('#findImagesButton')
          .should('be.visible')
          .click()
        cy.wait(1500)
      })

    // Wait for the iframe to load, then check what's there, then exclude cherry
    cy.get('iframe[src*="imgArrayTbl.php"]')
      .should('be.visible')
      .its('0.contentDocument.body')
      .should('not.be.empty')
      .then(cy.wrap)
      .within(() => {
        // Check the input value
        cy.get('#tagList')
          .should('have.value', 'produce')
          .should('have.attr', 'readonly')
        cy.wait(500)
        
        // Wait for search results and verify the specific image is present
        cy.get('img[data-objid="500b6984d64439bab876480128bdb420"]')
          .should('exist')
	  
        cy.wait(500)
	  
        cy.get('img[data-objid="1ceb66fc7c47d0fc91e9e21b541ed8d7"]')
          .should('exist')
	  
        cy.wait(500)

        // Type 'cherry' into the tag input and click search
        cy.get('#excludeTagInput')
          .should('be.visible')
          .type('cherry')
        cy.wait(1500)
        
          cy.get('#excludeImagesButton')
          .should('be.visible')
          .click()
        cy.wait(1500)
      })
      
    // Wait for the iframe to load, then check what's there
    cy.get('iframe[src*="imgArrayTbl.php"]')
      .should('be.visible')
      .its('0.contentDocument.body')
      .should('not.be.empty')
      .then(cy.wrap)
      .within(() => {
        // Check the input value
        cy.get('#tagList')
          .should('have.value', 'produce NOT cherry')
          .should('have.attr', 'readonly')
        cy.wait(500)
	  
        // Verify the cherry isn't present
        cy.get('img[data-objid="500b6984d64439bab876480128bdb420"]')
          .should('not.exist')
	  
        cy.wait(500)
	  
        // Verify the pepper image is present
        cy.get('img[data-objid="1ceb66fc7c47d0fc91e9e21b541ed8d7"]')
          .should('exist')
	  
        cy.wait(500)
      })
      
  })

    
  it('should preserve selected and excluded state when downloading an image', () => {
    cy.visit('/index.php')
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
          .type('produce')
        cy.wait(500)
        
        cy.get('#findImagesButton')
          .should('be.visible')
          .click()
        cy.wait(1500)
      })

    // Check expected images, then add exlusion
    cy.get('iframe[src*="imgArrayTbl.php"]')
      .should('be.visible')
      .its('0.contentDocument.body')
      .should('not.be.empty')
      .then(cy.wrap)
      .within(() => {
        // check for cherry
        cy.get('img[data-objid="500b6984d64439bab876480128bdb420"]')
          .should('exist')

        // check for pepper
        cy.get('img[data-objid="1ceb66fc7c47d0fc91e9e21b541ed8d7"]')
        .should('exist')

        cy.wait(500)

        // Type 'cherry' into the tag input and click search
        cy.get('#excludeTagInput')
          .should('be.visible')
          .type('cherry')
        cy.wait(500)
        
          cy.get('#excludeImagesButton')
          .should('be.visible')
          .click()
        cy.wait(1500)
      })

    // Click on the image to go to image_info
    cy.get('iframe[src*="imgArrayTbl.php"]')
      .should('be.visible')
      .its('0.contentDocument.body')
      .should('not.be.empty')
      .then(cy.wrap)
      .within(() => {
        cy.get('img[data-objid="1ceb66fc7c47d0fc91e9e21b541ed8d7"]')
          .click()
      })

    // Verify URL contains our state
    cy.url().should('include', 'tags=produce%20NOT%20cherry')

    // Click download
    cy.get('#return').should('be.visible') // Wait for page load
    cy.get('img[src="img/download.png"]').click()

    cy.wait(500)
    
    // After download completes, verify we're back on image_info with state preserved
    cy.url().should('include', 'image_info.php')
    cy.url().should('include', 'tags=produce%20NOT%20cherry')
  })

    
  it('make sure tag term ANDs are handled correctly', () => {
    cy.visit('/index.php')
    cy.wait(500)

    // Wait for the iframe to load
    cy.get('iframe[src*="imgArrayTbl.php"]')
      .should('be.visible')
      .its('0.contentDocument.body')
      .should('not.be.empty')
      .then(cy.wrap)
      .within(() => {
        // Type 'man' into the tag input and click search
        cy.get('#tagInput')
          .should('be.visible')
          .type('man')
        cy.wait(500)
        
        cy.get('#findImagesButton')
          .should('be.visible')
          .click()
        cy.wait(1500)

        // Type 'face' into the tag input and click search
        cy.get('#tagInput')
          .should('be.visible')
          .type('necktie')
        cy.wait(500)
        
        cy.get('#findImagesButton')
          .should('be.visible')
          .click()
        cy.wait(1500)

        // Type 'face' into the tag input and click search
        cy.get('#tagInput')
          .should('be.visible')
          .type('glasses')
        cy.wait(500)
        
        cy.get('#findImagesButton')
          .should('be.visible')
          .click()
        cy.wait(1500)
      })

    // Check an expected image and the tagList
    cy.get('iframe[src*="imgArrayTbl.php"]')
      .should('be.visible')
      .its('0.contentDocument.body')
      .should('not.be.empty')
      .then(cy.wrap)
      .within(() => {
        cy.get('#tagList')
          .should('have.value', 'man AND necktie AND glasses')
          .should('have.attr', 'readonly')

        cy.wait(500)

        // check for frank, then click for image_info
        cy.get('img[data-objid="fea684f1f114bff53a72d8545e336b7c"]')
          .should('exist')
          .click()
        cy.wait(1500)
      })

    cy.url().should('include', 'image_info.php')
    cy.wait(500)

    // Click the return button
    cy.get('#return').click()
    cy.wait(1500)

    // Check an expected image and the tagList
    cy.get('iframe[src*="imgArrayTbl.php"]')
      .should('be.visible')
      .its('0.contentDocument.body')
      .should('not.be.empty')
      .then(cy.wrap)
      .within(() => {
        cy.get('#tagList')
          .should('have.value', 'man AND necktie AND glasses')
          .should('have.attr', 'readonly')

        cy.wait(500)
      })
  })
    
})
