describe('Login Page', () => {
  beforeEach(() => {
    cy.visit('/login.php')
  })

  it('should show login form', () => {
    cy.get('form').should('be.visible')
    cy.get('input[name="uname"]').should('be.visible')
    cy.get('input[name="pswd"]').should('be.visible')
  })

  it('should show error with invalid credentials', () => {
    cy.get('input[name="uname"]').type('invalid')
    cy.get('input[name="pswd"]').type('wrongpassword')
    cy.get('form').submit()
    cy.contains('Invalid Username or Password').should('be.visible')
  })

  it('should redirect to index.php on successful login', () => {
    cy.get('input[name="uname"]').type('joe')
    cy.get('input[name="pswd"]').type('lemon')
    cy.get('form').submit()
    cy.url().should('include', 'index.php')
  })
})
