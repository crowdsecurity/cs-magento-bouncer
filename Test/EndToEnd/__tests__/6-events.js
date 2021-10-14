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
    onAdminGoToSettingsPage,
    onAdminSaveSettings,
    selectElement,
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

describe(`Log events in front`, () => {
    beforeAll(async () => {
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await removeAllDecisions();
        await setDefaultConfig();
    });

    beforeEach(async () => {
        await deleteFileContent(EVENT_LOG_PATH);
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
        const logContent = await getFileContent(EVENT_LOG_PATH);
        await expect(logContent).toMatch(
            `{"type":"M2_EVENT_CUSTOMER_LOGIN","ip":"${PROXY_IP}","x-forwarder-for-ip":"${CURRENT_IP}"`,
        );
        await expect(logContent).toMatch(`"customer_email":"${EMAIL}"`);
    });

    it("Should add to cart", async () => {
        await goToPublicPage("/simple-product-10.html");
        await page.click("#product-addtocart-button");
        await page.waitForLoadState("networkidle");
        await expect(page).toMatchText(
            ".message-success",
            /You added Simple Product 10/,
        );
        const logContent = await getFileContent(EVENT_LOG_PATH);
        await expect(logContent).toMatch(
            `{"type":"M2_EVENT_QUOTE_ADD_PRODUCT","ip":"${PROXY_IP}","x-forwarder-for-ip":"${CURRENT_IP}"`,
        );
    });

    it("Should place order", async () => {
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
        const logContent = await getFileContent(EVENT_LOG_PATH);
        await expect(logContent).toMatch(
            `{"type":"M2_EVENT_PAYMENT_PLACE","ip":"${PROXY_IP}","x-forwarder-for-ip":"${CURRENT_IP}","payment_method":"checkmo"`,
        );
        const element = await page.$(".order-number");

        let incrementId = await element.innerText();
        incrementId = incrementId
            .replace("<strong>", "")
            .replace("</strong>", "");
        await expect(logContent).toMatch(
            `{"type":"M2_EVENT_ORDER_PLACE","ip":"${PROXY_IP}","x-forwarder-for-ip":"${CURRENT_IP}","order_increment_id":"${incrementId}"`,
        );
        await expect(logContent).toMatch(`"customer_email":"${EMAIL}"`);
    });

    it("Should hide sensitive data", async () => {
        await goToAdmin();
        await onAdminGoToSettingsPage();
        await selectElement("crowdsec_bouncer_events_log_hide_sensitive", "1");
        await onAdminSaveSettings();
        await goToPublicPage("/simple-product-10.html");
        await page.click("#product-addtocart-button");
        await page.waitForLoadState("networkidle");
        await goToPublicPage("/checkout");
        await page.waitForLoadState("networkidle");
        await page.click(".action.continue.primary");
        await page.waitForLoadState("networkidle");
        await page.click(".action.primary.checkout");
        await page.waitForLoadState("networkidle");
        await wait(2000);
        await expect(page).toMatchTitle("Success Page");
        const logContent = await getFileContent(EVENT_LOG_PATH);
        await expect(logContent).toMatch(
            `{"type":"M2_EVENT_PAYMENT_PLACE","ip":"${PROXY_IP}","x-forwarder-for-ip":"${CURRENT_IP}","payment_method":"checkmo"`,
        );
        const element = await page.$(".order-number");

        let incrementId = await element.innerText();
        incrementId = incrementId
            .replace("<strong>", "")
            .replace("</strong>", "");
        await expect(logContent).toMatch(
            `{"type":"M2_EVENT_ORDER_PLACE","ip":"${PROXY_IP}","x-forwarder-for-ip":"${CURRENT_IP}","order_increment_id":"${incrementId}"`,
        );
        await expect(logContent).toMatch(`"customer_email"`);
        await expect(logContent).not.toMatch(`"customer_email":"${EMAIL}"`);
    });
});

describe(`Log events in admin`, () => {
    beforeEach(async () => {
        await deleteFileContent(EVENT_LOG_PATH);
        const logContent = await getFileContent(EVENT_LOG_PATH);
        await expect(logContent).toBe("");
    });

    it("Should log failed admin user login", async () => {
        await goToAdmin("admin/auth/logout/");
        await expect(page).toMatchText(
            ".message-success",
            /You have logged out/,
        );
        await page.fill("#username", "BAD_USER");
        await page.fill("#login", "BAD_PASSWORD");
        await page.click(".action-login");
        await page.waitForLoadState("networkidle");
        await expect(page).toMatchText(
            ".message-error",
            /The account sign-in was incorrect/,
        );
        const element = await page.$(".message.message-error > div");
        const errorMessage = await element.innerText();
        const logContent = await getFileContent(EVENT_LOG_PATH);
        await expect(logContent).toMatch(
            `{"type":"M2_EVENT_USER_LOGIN_FAILED","ip":"${PROXY_IP}","x-forwarder-for-ip":"${CURRENT_IP}","user_name":"BAD_USER","exception_message":"${errorMessage}"`,
        );
    });
});
