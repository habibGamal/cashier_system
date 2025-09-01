describe('Manage Web Order Page', () => {
  beforeEach(() => {
    cy.login()
  })

  it('should access web order from orders index', () => {
    cy.navigateToOrders()

    // Switch to web delivery tab
    cy.contains('ويب ديلفري').click()
    cy.url().should('include', '#web_delivery')

    // Check if there are any web orders to test
    cy.get('body').then(($body) => {
      if ($body.find('.ant-card').length > 0) {
        // Click on first web order
        cy.get('.ant-card').first().click()
        cy.url().should('include', '/web-orders/manage/')
      } else {
        cy.log('No web delivery orders found')
      }
    })
  })

  it('should access web takeaway orders', () => {
    cy.navigateToOrders()

    // Switch to web takeaway tab
    cy.contains('ويب تيك اواي').click()
    cy.url().should('include', '#web_takeaway')

    // Check if there are any web takeaway orders
    cy.get('body').then(($body) => {
      if ($body.find('.ant-card').length > 0) {
        cy.get('.ant-card').first().click()
        cy.url().should('include', '/web-orders/manage/')
      } else {
        cy.log('No web takeaway orders found')
      }
    })
  })

  context('When managing an existing web order', () => {
    beforeEach(() => {
      // Try to find and navigate to a web order
      cy.navigateToOrders()
      cy.contains('ويب ديلفري').click()

      cy.get('body').then(($body) => {
        if ($body.find('.ant-card').length > 0) {
          cy.get('.ant-card').first().click()
        }
      })
    })

    it('should display web order details', () => {
      cy.url().then((url) => {
        if (url.includes('/web-orders/manage/')) {
          // Check web order specific fields
          cy.contains('بيانات الطلب').should('be.visible')
          cy.contains('نوع الطلب').should('be.visible')
          cy.contains('رقم الطلب المرجعي').should('be.visible')
          cy.contains('اسم العميل').should('be.visible')
          cy.contains('رقم العميل').should('be.visible')
        }
      })
    })

    it('should display web order status badge', () => {
      cy.url().then((url) => {
        if (url.includes('/web-orders/manage/')) {
          cy.get('.ant-badge-ribbon').should('be.visible')
        }
      })
    })

    it('should display web order action buttons', () => {
      cy.url().then((url) => {
        if (url.includes('/web-orders/manage/')) {
          // Basic buttons should be present
          cy.contains('طباعة الفاتورة').should('be.visible')
          cy.contains('طباعة في المطبخ').should('be.visible')
          cy.contains('حفظ').should('be.visible')
          cy.contains('إلغاء').should('be.visible')
        }
      })
    })

    it('should show order acceptance button for pending orders', () => {
      cy.url().then((url) => {
        if (url.includes('/web-orders/manage/')) {
          cy.get('body').then(($body) => {
            if ($body.text().includes('في الإنتظار')) {
              cy.contains('قبول الطلب').should('be.visible')
            }
          })
        }
      })
    })

    it('should handle order notes modal', () => {
      cy.url().then((url) => {
        if (url.includes('/web-orders/manage/')) {
          cy.contains('ملاحظات الطلب').click()

          // Modal should open
          cy.get('.ant-modal').should('be.visible')

          // Close modal
          cy.get('.ant-modal-close').click()
          cy.get('.ant-modal').should('not.exist')
        }
      })
    })

    it('should save web order changes', () => {
      cy.url().then((url) => {
        if (url.includes('/web-orders/manage/')) {
          // Click save button
          cy.contains('حفظ').click()

          // Should show message
          cy.get('.ant-message').should('be.visible')
        }
      })
    })

    it('should display web order payment details', () => {
      cy.url().then((url) => {
        if (url.includes('/web-orders/manage/')) {
          // Check payment section
          cy.contains('الحساب').should('be.visible')
          cy.contains('المجموع').should('be.visible')
          cy.contains('الإجمالي').should('be.visible')

          // Web orders specific field
          cy.contains('فرق تسعير').should('be.visible')
        }
      })
    })

    it('should navigate back to orders list', () => {
      cy.url().then((url) => {
        if (url.includes('/web-orders/manage/')) {
          // Click back button
          cy.get('[data-icon="right"]').click()

          // Should return to orders page
          cy.url().should('include', '/orders')
        }
      })
    })
  })

  context('Web order workflow actions', () => {
    beforeEach(() => {
      cy.navigateToOrders()
      cy.contains('ويب ديلفري').click()

      cy.get('body').then(($body) => {
        if ($body.find('.ant-card').length > 0) {
          cy.get('.ant-card').first().click()
        }
      })
    })

    it('should handle order acceptance if pending', () => {
      cy.url().then((url) => {
        if (url.includes('/web-orders/manage/')) {
          cy.get('body').then(($body) => {
            if ($body.text().includes('قبول الطلب')) {
              cy.contains('قبول الطلب').click()

              // Confirm in popup
              cy.get('.ant-popconfirm').should('be.visible')
              cy.contains('نعم').click()
            }
          })
        }
      })
    })

    it('should handle delivery actions for web delivery orders', () => {
      cy.url().then((url) => {
        if (url.includes('/web-orders/manage/')) {
          cy.get('body').then(($body) => {
            // Check for delivery-specific buttons
            if ($body.text().includes('خرج للتوصيل')) {
              cy.contains('خرج للتوصيل').should('be.visible')
            }

            if ($body.text().includes('بيانات السائق')) {
              cy.contains('بيانات السائق').should('be.visible')
            }
          })
        }
      })
    })

    it('should handle order cancellation', () => {
      cy.url().then((url) => {
        if (url.includes('/web-orders/manage/')) {
          cy.contains('إلغاء').click()

          // Confirm cancellation
          cy.get('.ant-popconfirm').should('be.visible')
          cy.contains('لا').click() // Cancel the cancellation for safety
        }
      })
    })
  })
})
