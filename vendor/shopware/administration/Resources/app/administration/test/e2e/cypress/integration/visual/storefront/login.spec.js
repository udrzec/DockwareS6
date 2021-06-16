import AccountPageObject from '../../../support/pages/account.page-object';

describe('Account - Login: Visual tests', () => {
    beforeEach(() => {
        cy.setToInitialState()
            .then(() => {
                return cy.createCustomerFixture()
            })
    });

    it('@visual: check appearance of basic storefront login workflow', () => {
        const page = new AccountPageObject();
        cy.visit('/account/login');

        // Take snapshot for visual testing
        cy.takeSnapshot('Account overview after login', page.elements.loginCard, { widths: [375, 1920] });

        cy.get('#loginMail').typeAndCheckStorefront('test@example.com');
        cy.get('#loginPassword').typeAndCheckStorefront('shopware');
        cy.get(`${page.elements.loginSubmit} [type="submit"]`).click();

        cy.get('.account-welcome h1').should((element) => {
            expect(element).to.contain('Overview');
        });
    });
});
