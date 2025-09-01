// Simple utility functions for order testing

export const OrderHelpers = {
  // Check if an order exists in a specific tab
  findOrderInTab: (tabName: string) => {
    cy.contains(tabName).click()
    return cy.get('body').then(($body) => {
      return $body.find('.ant-card').length > 0
    })
  },

  // Click on the first available order in current tab
  clickFirstOrder: () => {
    cy.get('.ant-card').first().click()
  },

  // Test modal opening and closing
  testModal: (buttonText: string) => {
    cy.contains(buttonText).click()
    cy.get('.ant-modal').should('be.visible')
    cy.get('.ant-modal-close').click()
    cy.get('.ant-modal').should('not.exist')
  },

  // Test button existence based on order type
  checkButtonsForOrderType: (orderType: 'regular' | 'web') => {
    const commonButtons = ['طباعة الفاتورة', 'طباعة في المطبخ', 'حفظ']

    if (orderType === 'regular') {
      commonButtons.push('بيانات العميل', 'انهاء الطلب')
    } else {
      commonButtons.push('إلغاء')
    }

    commonButtons.forEach(button => {
      cy.contains(button).should('be.visible')
    })
  }
}
