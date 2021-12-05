Cypress.Commands.add("login", () => {

    // Clear existing cookies
    cy.request(Cypress.env('baseUrl') + '/logout');

    const location = cy.request({
        url: Cypress.env('baseUrl') + '/login'
    })

    .then((response) => {
        return cy.request(Cypress.env('baseUrl') + '/login')
            .its('body')
            .then((body) => {
                // we can use Cypress.$ to parse the string body
                // thus enabling us to query into it easily
                const $html = Cypress.$(body)

                return $html.find('input[name=_csrf_token]').val()
            })
            .then((csrf) => {
                return cy.request({
                    method: 'POST',
                    url: Cypress.env('baseUrl') + '/login',
                    form: true,
                    followRedirect: false,
                    body: {
                        '_username': Cypress.env('user'),
                        '_password': Cypress.env('password'),
                        '_csrf_token': csrf,
                    },
                })
            })
    })

    .then((response) => {
        console.log(response);

        // If already solved, continue
        if (response === true) {
            return true;
        }

        const location  = response.headers.location;

        // Either we already are identified (and redirected to Blackfire.io)
        if (location.startsWith(Cypress.env('baseUrl'))) {
            return true;
        }
    })
    cy.visit(Cypress.env('baseUrl'));
});
