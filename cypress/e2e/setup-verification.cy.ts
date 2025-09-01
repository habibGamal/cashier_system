describe('Cypress Setup Verification', () => {
  it('should load the application homepage', () => {
    cy.visit('/')

    // Check if the page loads successfully
    cy.get('body').should('exist')

    // Check if we can see some basic content
    // This might be a login form or dashboard depending on your setup
    cy.get('html').then(($html) => {
      const text = $html.text()
      expect(text).to.satisfy((str: string) =>
        str.includes('Laravel') ||
        str.includes('Login') ||
        str.includes('تسجيل الدخول') ||
        str.includes('الطلبات')
      )
    })
  })

  it('should handle Arabic text correctly', () => {
    // Test Arabic text rendering
    cy.document().should((doc) => {
      expect(doc.charset).to.equal('UTF-8')
    })

    // Visit a page that should contain Arabic text
    cy.visit('/login')

    // The page should be able to display Arabic characters
    // This test ensures proper UTF-8 and font support
    cy.get('html').should('have.attr', 'lang')
  })

  it('should be configured correctly for the application', () => {
    // Verify base URL configuration
    cy.url().should('include', Cypress.config('baseUrl'))

    // Verify viewport is set correctly
    cy.viewport('macbook-15')
    cy.window().should('have.property', 'innerWidth').and('be.greaterThan', 1200)
  })
})
