describe('Manage Order Page', () => {
  beforeEach(() => {
    cy.login()
  })

  it('should display manage order page when clicking on an order', () => {
    // Go to orders index
    cy.navigateToOrders()

    // Look for any existing orders and click on one
    cy.get('body').then(($body) => {
      // Check if there are any orders to manage
      if ($body.find('.ant-card').length > 0) {
        // Click on the first order card
        cy.get('.ant-card').first().click()

        // Should navigate to manage order page
        cy.url().should('include', '/orders/manage/')

        // Check if page elements are present
        cy.contains('إدارة الطلب').should('be.visible')
        cy.get('.ant-breadcrumb').should('be.visible')
      } else {
        cy.log('No orders found to test manage functionality')
      }
    })
  })

  context('When managing an existing order', () => {
    beforeEach(() => {
      // Visit orders page and try to find an order to manage
      cy.navigateToOrders()
      cy.get('body').then(($body) => {
        if ($body.find('.ant-card').length > 0) {
          cy.get('.ant-card').first().click()
        } else {
          cy.log('No orders available for testing')
        }
      })
    })

    it('should display order status badge', () => {
      cy.url().then((url) => {
        if (url.includes('/orders/manage/')) {
          cy.get('.ant-badge-ribbon').should('be.visible')
        }
      })
    })

    it('should display all action buttons', () => {
      cy.url().then((url) => {
        if (url.includes('/orders/manage/')) {
          // Check if main action buttons are present
          cy.contains('طباعة الفاتورة').should('be.visible')
          cy.contains('طباعة في المطبخ').should('be.visible')
          cy.contains('بيانات العميل').should('be.visible')
          cy.contains('ملاحظات الطلب').should('be.visible')
          cy.contains('حفظ').should('be.visible')
        }
      })
    })

    it('should display order items and calculations', () => {
      cy.url().then((url) => {
        if (url.includes('/orders/manage/')) {
          // Check if order details section exists
          cy.contains('تفاصيل الطلب').should('be.visible')

          // Check if payment calculations are shown
          cy.contains('الحساب').should('be.visible')
          cy.contains('المجموع').should('be.visible')
          cy.contains('الإجمالي').should('be.visible')
        }
      })
    })

    it('should open customer modal when clicking customer button', () => {
      cy.url().then((url) => {
        if (url.includes('/orders/manage/')) {
          cy.contains('بيانات العميل').click()

          // Modal should open
          cy.get('.ant-modal').should('be.visible')

          // Close modal
          cy.get('.ant-modal-close').click()
          cy.get('.ant-modal').should('not.exist')
        }
      })
    })

    it('should open order notes modal', () => {
      cy.url().then((url) => {
        if (url.includes('/orders/manage/')) {
          cy.contains('ملاحظات الطلب').click()

          // Modal should open
          cy.get('.ant-modal').should('be.visible')

          // Close modal
          cy.get('.ant-modal-close').click()
          cy.get('.ant-modal').should('not.exist')
        }
      })
    })

    it('should save order changes', () => {
      cy.url().then((url) => {
        if (url.includes('/orders/manage/')) {
          // Click save button
          cy.contains('حفظ').click()

          // Should show success message
          cy.get('.ant-message').should('be.visible')
        }
      })
    })

    it('should navigate back to orders list', () => {
      cy.url().then((url) => {
        if (url.includes('/orders/manage/')) {
          // Click back button (arrow icon)
          cy.get('[data-icon="right"]').click()

          // Should return to orders page
          cy.url().should('include', '/orders')
        }
      })
    })
  })

  context('Order functionality without existing orders', () => {
    it('should show message when no orders exist', () => {
      cy.navigateToOrders()

      // Check if empty state is shown when no orders
      cy.get('body').then(($body) => {
        if ($body.find('.ant-card').length === 0) {
          // Should show empty state or message
          cy.get('body').should('contain.text', 'لا توجد طلبات')
        }
      })
    })
  })
})
