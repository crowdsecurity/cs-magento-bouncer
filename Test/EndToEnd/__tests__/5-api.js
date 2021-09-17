const { testCustomerRest } = require("../utils/restClient");
const { testSoap } = require("../utils/soapClient");
const {
    removeAllDecisions,
    selectElement,
    onAdminSaveSettings,
    onLoginPageLoginAsAdmin,
    goToAdmin,
    goToSettingsPage,
    goToPublicPage,
    setDefaultConfig,
    banIpForSeconds,
} = require("../utils/helpers");

const { CURRENT_IP } = require("../utils/constants");

describe(`Call REST Api `, () => {
    beforeAll(async () => {
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await removeAllDecisions();
        await setDefaultConfig();
    });

    it("Config OFF: Should let REST call pass with clean IP", async () => {
        const response = await testCustomerRest();
        await expect(response.status).not.toBe(401);
        await expect(response.status).not.toBe(403);
    });

    it("Config OFF: Should let REST call pass with bad IP", async () => {
        await banIpForSeconds(15 * 60, CURRENT_IP);
        const response = await testCustomerRest();
        await expect(response.status).not.toBe(401);
        await expect(response.status).not.toBe(403);
    });

    it("Config ON: Should NOT let REST call pass with bad IP", async () => {
        await goToSettingsPage();
        await selectElement(
            "crowdsec_bouncer_general_bouncing_api_enabled",
            "1",
        );
        await onAdminSaveSettings();
        const response = await testCustomerRest();
        await expect(response.status).toBe(403);
    });

    it("Config ON: Should let REST call pass with clean IP", async () => {
        await removeAllDecisions();
        const response = await testCustomerRest();
        await expect(response.status).not.toBe(401);
        await expect(response.status).not.toBe(403);
    });
});

describe(`Call SOAP api`, () => {
    beforeAll(async () => {
        await removeAllDecisions();
    });
    it("Config ON: Should access SOAP endpoint with clean IP", async () => {
        const response = await testSoap();
        await expect(JSON.stringify(response)).toMatch(
            /directoryCurrencyInformationAcquirerV1Service/,
        );
    });

    it("Config ON: Should not access SOAP endpoint with bad ip", async () => {
        await banIpForSeconds(15 * 60, CURRENT_IP);
        const response = await testSoap();
        await expect(JSON.stringify(response)).toBe('"ERROR"');
    });

    it("Config OFF: Should access SOAP endpoint with bad IP", async () => {
        await goToSettingsPage();
        await selectElement(
            "crowdsec_bouncer_general_bouncing_api_enabled",
            "0",
        );
        await onAdminSaveSettings();
        const response = await testSoap();
        await expect(JSON.stringify(response)).toMatch(
            /directoryCurrencyInformationAcquirerV1Service/,
        );
    });

    it("Config OFF: Should access SOAP endpoint with clean ip", async () => {
        await removeAllDecisions();
        await banIpForSeconds(15 * 60, CURRENT_IP);
        const response = await testSoap();
        await expect(JSON.stringify(response)).toMatch(
            /directoryCurrencyInformationAcquirerV1Service/,
        );
    });
});

describe(`Access GraphQL endpoint`, () => {
    beforeAll(async () => {
        await removeAllDecisions();
    });
    it("Config ON: Should access GraphQL endpoint with clean IP", async () => {
        await goToSettingsPage();
        await selectElement(
            "crowdsec_bouncer_general_bouncing_api_enabled",
            "1",
        );
        await onAdminSaveSettings();
        const response = await goToPublicPage("/graphql");
        await expect(response.status()).toBe(200);
    });

    it("Config ON: Should not access GraphQL endpoint with bad ip", async () => {
        await banIpForSeconds(15 * 60, CURRENT_IP);
        const response = await goToPublicPage("/graphql");
        await expect(response.status()).toBe(403);
    });

    it("Config OFF: Should access GraphQL endpoint with bad IP", async () => {
        await goToSettingsPage();
        await selectElement(
            "crowdsec_bouncer_general_bouncing_api_enabled",
            "0",
        );
        await onAdminSaveSettings();
        const response = await goToPublicPage("/graphql");
        await expect(response.status()).toBe(200);
    });

    it("Config OFF: Should access GraphQL endpoint with clean ip", async () => {
        await removeAllDecisions();
        const response = await goToPublicPage("/graphql");
        await expect(response.status()).toBe(200);
    });
});
