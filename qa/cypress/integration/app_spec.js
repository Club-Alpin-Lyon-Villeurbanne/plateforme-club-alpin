describe('Login behavior', function() {
    it('Redirect to home on authentication', function() {
        cy.login();

        cy.contains('Bonjour');
    });
});
