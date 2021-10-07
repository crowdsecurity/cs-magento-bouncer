/* eslint-disable no-undef */
const {
    removeAllDecisions,
    onLoginPageLoginAsAdmin,
    goToAdmin,
    goToPublicPage,
    setDefaultConfig,
    deleteFileContent,
    getFileContent,
    fillInput,
    fillByName,
    selectByName,
    wait,
} = require("../utils/helpers");

const { CURRENT_IP, PROXY_IP, EVENT_LOG_PATH } = require("../utils/constants");

const CURRENT_TIME = `${Date.now()}`;
const FIRSTNAME = `E2EFirstname-${CURRENT_TIME}`;
const LASTNAME = `E2ELastname-${CURRENT_TIME}`;
const EMAIL = `${FIRSTNAME}${LASTNAME}@crowdsec.net`;
const PASSWORD = `PwD-${CURRENT_TIME}`;
const STREET = "Street";
const CITY = "City";
const POSTCODE = "12345";
const PHONE = "0607080910";

describe(`Create customer`, () => {
    beforeAll(async () => {
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await removeAllDecisions();
        await setDefaultConfig();
        await deleteFileContent(EVENT_LOG_PATH);
    });

    it("Should have empty log", async () => {
        const logContent = await getFileContent(EVENT_LOG_PATH);
        await expect(logContent).toBe("");
    });

    it("Should create and login a customer", async () => {
        await goToPublicPage("/customer/account/create");
        await fillInput("firstname", FIRSTNAME);
        await fillInput("lastname", LASTNAME);
        await fillInput("email_address", EMAIL);
        await fillInput("password", PASSWORD);
        await fillInput("password-confirmation", PASSWORD);
        await page.click(".action.submit.primary");
        await page.waitForLoadState("networkidle");
        await expect(page).toMatchText(
            ".message-success",
            /Thank you for registering/,
        );
        const logContent = await getFileContent(EVENT_LOG_PATH);
        await expect(logContent).toMatch(
            `{"type":"M2_EVENT_CUSTOMER_REGISTER","ip":"${PROXY_IP}","x-forwarder-for-ip":"${CURRENT_IP}"`,
        );
        await expect(logContent).toMatch(`"customer_email":"${EMAIL}"`);
        await expect(logContent).toMatch(
            `{"type":"M2_EVENT_CUSTOMER_LOGIN","ip":"${PROXY_IP}","x-forwarder-for-ip":"${CURRENT_IP}"`,
        );
    });

    it("Should Log out and login ", async () => {
        await deleteFileContent(EVENT_LOG_PATH);
        let logContent = await getFileContent(EVENT_LOG_PATH);
        await expect(logContent).toBe("");
        await page.click(".action.switch");
        await page.click('li.link:has-text("Sign Out")');
        await page.waitForLoadState("networkidle");
        await page.click('li.link:has-text("Sign In")');
        await page.waitForLoadState("networkidle");
        await fillInput("email", EMAIL);
        await fillInput("pass", PASSWORD);
        await page.click(".action.login.primary");
        await page.waitForLoadState("networkidle");
        await expect(page).toMatchText(".logged-in", /Welcome/);
        logContent = await getFileContent(EVENT_LOG_PATH);
        await expect(logContent).toMatch(
            `{"type":"M2_EVENT_CUSTOMER_LOGIN","ip":"${PROXY_IP}","x-forwarder-for-ip":"${CURRENT_IP}"`,
        );
        await expect(logContent).toMatch(`"customer_email":"${EMAIL}"`);
    });

    it("Should add to cart", async () => {
        await deleteFileContent(EVENT_LOG_PATH);
        let logContent = await getFileContent(EVENT_LOG_PATH);
        await expect(logContent).toBe("");
        await goToPublicPage("/category-1/simple-product-10.html");
        await page.click("#product-addtocart-button");
        await page.waitForLoadState("networkidle");
        await expect(page).toMatchText(
            ".message-success",
            /You added Simple Product 10/,
        );
        logContent = await getFileContent(EVENT_LOG_PATH);
        await expect(logContent).toMatch(
            `{"type":"M2_EVENT_QUOTE_ADD_PRODUCT","ip":"${PROXY_IP}","x-forwarder-for-ip":"${CURRENT_IP}"`,
        );
    });

    it("Should place order", async () => {
        await deleteFileContent(EVENT_LOG_PATH);
        let logContent = await getFileContent(EVENT_LOG_PATH);
        await expect(logContent).toBe("");
        await goToPublicPage("/checkout");
        await page.waitForLoadState("networkidle");
        await fillByName("city", CITY);
        await fillByName("postcode", POSTCODE);
        await fillByName("telephone", PHONE);
        await selectByName("country_id", "FR");
        await fillByName("street\\[0\\]", STREET);
        await page.click(".action.continue.primary");
        await page.waitForLoadState("networkidle");
        await page.click(".action.primary.checkout");
        await page.waitForLoadState("networkidle");
        await wait(2000);
        await expect(page).toMatchTitle("Success Page");
        logContent = await getFileContent(EVENT_LOG_PATH);
        await expect(logContent).toMatch(
            `{"type":"M2_EVENT_PAYMENT_PLACE","ip":"${PROXY_IP}","x-forwarder-for-ip":"${CURRENT_IP}","payment_method":"checkmo"`,
        );
        const element = await page.$(".order-number");

        const incrementId = await element.innerText();
        await expect(logContent).toMatch(
            `{"type":"M2_EVENT_ORDER_PLACE","ip":"${PROXY_IP}","x-forwarder-for-ip":"${CURRENT_IP}","order_increment_id":"${incrementId
                .replace("<strong>", "")
                .replace("</strong>", "")}"`,
        );
    });
});
