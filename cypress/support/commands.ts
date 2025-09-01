// ***********************************************
// Custom commands for Orders Management Testing
// ***********************************************

// Login command
Cypress.Commands.add(
    "login",
    (email = "admin@example.com", password = "password") => {
        cy.visit("/admin/login");
        cy.get('input[id="data.email"]').clear().type(email);
        cy.get('input[id="data.password"]').clear().type(password);
        cy.contains('button', 'تسجيل الدخول').click();
        cy.url().should("not.include", "/login");
    }
);

// Navigate to orders page
Cypress.Commands.add("navigateToOrders", () => {
    cy.visit("/orders");
    cy.url().should("include", "/orders");
});

// Wait for page to be fully loaded
Cypress.Commands.add("waitForPageLoad", () => {
    cy.get("body").should("be.visible");
    cy.get(".ant-spin").should("not.exist"); // Wait for any loading spinners to disappear
});
