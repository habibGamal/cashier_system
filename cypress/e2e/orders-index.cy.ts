describe('Orders Index Page', () => {
  beforeEach(() => {
    cy.login()
    cy.navigateToOrders()
  })

  it('should display the orders page with all tabs', () => {
    // Check if page title is correct
    cy.title().should('include', 'إدارة الطلبات')

    // Check if all tabs are present
    cy.contains('الصالة').should('be.visible')
    cy.contains('الديلفري').should('be.visible')
    cy.contains('التيك اواي').should('be.visible')
    cy.contains('طلبات').should('be.visible')
    cy.contains('ويب ديلفري').should('be.visible')
    cy.contains('ويب تيك اواي').should('be.visible')
    cy.contains('مصاريف').should('be.visible')
    cy.contains('ملغي').should('be.visible')
    cy.contains('منتهي').should('be.visible')
  })

  it('should display user information and end shift button', () => {
    // Check if user email is displayed
    cy.get('[data-testid="user-info"]').should('be.visible')

    // Check if end shift button is present
    cy.contains('انهاء الشيفت').should('be.visible')
  })

  it('should switch between different order type tabs', () => {
    // Test switching to delivery tab
    cy.contains('الديلفري').click()
    cy.url().should('include', '#delivery')

    // Test switching to takeaway tab
    cy.contains('التيك اواي').click()
    cy.url().should('include', '#takeaway')

    // Test switching to web delivery tab
    cy.contains('ويب ديلفري').click()
    cy.url().should('include', '#web_delivery')

    // Test switching back to dine in
    cy.contains('الصالة').click()
    cy.url().should('include', '#dine_in')
  })

  it('should handle end shift confirmation', () => {
    // Click end shift button
    cy.contains('انهاء الشيفت').click()

    // Check if confirmation dialog appears
    cy.get('.ant-popover').should('be.visible')
    cy.contains('هل انت متأكد؟').should('be.visible')

    // Cancel the action
    cy.contains('لا').click()
    cy.get('.ant-popover').should('not.exist')
  })

  it('should display orders in appropriate tabs', () => {
    // Check each tab for content structure
    const tabs = [
      { name: 'الصالة', hash: 'dine_in' },
      { name: 'الديلفري', hash: 'delivery' },
      { name: 'التيك اواي', hash: 'takeaway' },
      { name: 'ويب ديلفري', hash: 'web_delivery' },
      { name: 'ويب تيك اواي', hash: 'web_takeaway' }
    ]

    tabs.forEach(tab => {
      cy.contains(tab.name).click()
      cy.url().should('include', `#${tab.hash}`)
      // Tab content should be visible (empty state or orders)
      cy.get('.ant-tabs-tabpane-active').should('be.visible')
    })
  })
})
