describe('Orders Management Integration Tests', () => {
  beforeEach(() => {
    cy.login()
  })

  it('should navigate through orders workflow', () => {
    // Start at orders index
    cy.navigateToOrders()
    cy.contains('إدارة الطلبات').should('be.visible')

    // Check all tabs are accessible
    const tabs = ['الصالة', 'الديلفري', 'التيك اواي', 'ويب ديلفري', 'ويب تيك اواي']

    tabs.forEach(tab => {
      cy.contains(tab).click()
      cy.get('.ant-tabs-tabpane-active').should('be.visible')
    })
  })

  it('should test complete order management workflow if orders exist', () => {
    cy.navigateToOrders()

    // Look for any existing orders in any tab
    const tabs = [
      { name: 'الصالة', type: 'regular' },
      { name: 'الديلفري', type: 'regular' },
      { name: 'التيك اواي', type: 'regular' },
      { name: 'ويب ديلفري', type: 'web' },
      { name: 'ويب تيك اواي', type: 'web' }
    ]

    let orderFound = false

    tabs.forEach(tab => {
      if (!orderFound) {
        cy.contains(tab.name).click()

        cy.get('body').then(($body) => {
          if ($body.find('.ant-card').length > 0) {
            orderFound = true
            cy.log(`Found order in ${tab.name} tab`)

            // Click on first order
            cy.get('.ant-card').first().click()

            // Should navigate to manage page
            cy.url().should('match', /\/(orders|web-orders)\/manage\//)

            // Test basic functionality
            cy.contains('حفظ').should('be.visible')
            cy.contains('طباعة الفاتورة').should('be.visible')

            // Test modal opening
            cy.contains('ملاحظات الطلب').click()
            cy.get('.ant-modal').should('be.visible')
            cy.get('.ant-modal-close').click()

            // Navigate back
            cy.get('[data-icon="right"]').click()
            cy.url().should('include', '/orders')
          }
        })
      }
    })

    // If no orders found, just verify the interface works
    if (!orderFound) {
      cy.log('No orders found for testing - verifying interface only')
      cy.contains('الصالة').should('be.visible')
    }
  })

  it('should handle responsive behavior across all pages', () => {
    const viewports = [
      { width: 375, height: 667, name: 'Mobile' },
      { width: 768, height: 1024, name: 'Tablet' },
      { width: 1280, height: 720, name: 'Desktop' }
    ]

    viewports.forEach(viewport => {
      cy.viewport(viewport.width, viewport.height)
      cy.log(`Testing ${viewport.name} view`)

      // Test orders index responsiveness
      cy.navigateToOrders()
      cy.contains('الصالة').should('be.visible')
      cy.get('.ant-tabs').should('be.visible')

      // If orders exist, test manage page responsiveness
      cy.get('body').then(($body) => {
        if ($body.find('.ant-card').length > 0) {
          cy.get('.ant-card').first().click()

          cy.url().then((url) => {
            if (url.includes('/manage/')) {
              cy.contains('حفظ').should('be.visible')
              cy.get('.ant-col').should('exist')
            }
          })
        }
      })
    })
  })

  it('should test error handling and edge cases', () => {
    // Test invalid URLs
    cy.visit('/orders/manage/999999', { failOnStatusCode: false })
    cy.get('body').then(($body) => {
      const text = $body.text()
      expect(text).to.satisfy((str: string) =>
        str.includes('404') || str.includes('Not Found')
      )
    })

    // Navigate back to valid page
    cy.navigateToOrders()
    cy.contains('الصالة').should('be.visible')
  })

  it('should test Arabic text rendering and RTL layout', () => {
    cy.navigateToOrders()

    // Verify Arabic text is properly displayed
    cy.contains('الصالة').should('be.visible')
    cy.contains('الديلفري').should('be.visible')
    cy.contains('انهاء الشيفت').should('be.visible')

    // Check RTL layout
    cy.get('html').then(($html) => {
      const dir = $html.attr('dir')
      const direction = $html.css('direction')
      expect(dir === 'rtl' || direction === 'rtl').to.be.true
    })
  })

  it('should handle print functionality', () => {
    cy.navigateToOrders()

    // Find an order and test print
    cy.get('body').then(($body) => {
      if ($body.find('.ant-card').length > 0) {
        cy.get('.ant-card').first().click()

        cy.url().then((url) => {
          if (url.includes('/manage/')) {
            // Mock window.print to test print functionality
            cy.window().then((win) => {
              cy.stub(win, 'print').as('windowPrint')
            })

            cy.contains('طباعة الفاتورة').click()
            cy.get('@windowPrint').should('have.been.called')
          }
        })
      }
    })
  })

  it('should test keyboard shortcuts', () => {
    cy.navigateToOrders()

    // Find an order to test shortcuts
    cy.get('body').then(($body) => {
      if ($body.find('.ant-card').length > 0) {
        cy.get('.ant-card').first().click()

        cy.url().then((url) => {
          if (url.includes('/manage/')) {
            // Test F8 shortcut for save
            cy.get('body').type('{F8}')

            // Test F9 shortcut for print (if implemented)
            cy.get('body').type('{F9}')
          }
        })
      }
    })
  })

  it('should test all modal functionalities', () => {
    cy.navigateToOrders()

    // Test modals if orders exist
    cy.get('body').then(($body) => {
      if ($body.find('.ant-card').length > 0) {
        cy.get('.ant-card').first().click()

        cy.url().then((url) => {
          if (url.includes('/manage/')) {
            const modalButtons = [
              'ملاحظات الطلب',
              'بيانات العميل',
              'طباعة في المطبخ'
            ]

            modalButtons.forEach(buttonText => {
              cy.get('body').then(($modalBody) => {
                if ($modalBody.text().includes(buttonText)) {
                  cy.contains(buttonText).click()
                  cy.get('.ant-modal').should('be.visible')
                  cy.get('.ant-modal-close').click()
                  cy.get('.ant-modal').should('not.exist')
                }
              })
            })
          }
        })
      }
    })
  })
})
