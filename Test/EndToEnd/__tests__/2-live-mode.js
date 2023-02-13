/* eslint-disable no-undef */
const {
    CURRENT_IP,
    PROXY_IP,
    BOUNCER_CERT_FILE,
    AGENT_CERT_FILE,
    BOUNCER_KEY_FILE,
    CA_CERT_FILE,
    TLS_PATH,
} = require("../utils/constants");

const {
    publicHomepageShouldBeBanWall,
    publicHomepageShouldBeCaptchaWallWithMentions,
    publicHomepageShouldBeCaptchaWallWithoutMentions,
    publicHomepageShouldBeAccessible,
    publicHomepageShouldBeCaptchaWall,
    adminPageShouldBeAccessible,
    adminPageShouldBeBanWall,
    banIpForSeconds,
    captchaIpForSeconds,
    removeAllDecisions,
    selectElement,
    onAdminSaveSettings,
    onLoginPageLoginAsAdmin,
    goToAdmin,
    goToSettingsPage,
    wait,
    fillInput,
    fillByName,
    setDefaultConfig,
    deleteFileContent,
    getFileContent,
    goToPublicPage,
    runCacheAction,
} = require("../utils/helpers");
const { addDecision } = require("../utils/watcherClient");

const { DEBUG_LOG_PATH } = require("../utils/constants");

describe(`Live mode run`, () => {
    beforeAll(async () => {
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await removeAllDecisions();
        await setDefaultConfig();
    });

    it("Should display the homepage with no remediation", async () => {
        await publicHomepageShouldBeAccessible();
    });
    it("Should display a captcha wall with mentions", async () => {
        await captchaIpForSeconds(15 * 60, CURRENT_IP);
        await publicHomepageShouldBeCaptchaWallWithMentions();
    });

    it("Should display a captcha wall without mentions", async () => {
        await goToSettingsPage();
        await selectElement(
            "crowdsec_bouncer_advanced_remediation_hide_mentions",
            "1",
        );
        await onAdminSaveSettings();
        await publicHomepageShouldBeCaptchaWallWithoutMentions();
    });

    it("Should refresh image", async () => {
        await runCacheAction("captcha-phrase", `&ip=${CURRENT_IP}`);
        const phrase = await page.$eval("h1", (el) => el.innerText);
        await publicHomepageShouldBeCaptchaWall();
        await page.click("#refresh_link");
        await runCacheAction("captcha-phrase", `&ip=${CURRENT_IP}`);
        const newPhrase = await page.$eval("h1", (el) => el.innerText);
        await expect(newPhrase).not.toEqual(phrase);
    });

    it("Should show error message", async () => {
        await publicHomepageShouldBeCaptchaWall();
        expect(await page.locator(".error").count()).toBeFalsy();
        await fillByName("phrase", "bad-value");
        await page.locator('button:text("CONTINUE")').click();
        expect(await page.locator(".error").count()).toBeTruthy();
    });

    it("Should solve the captcha", async () => {
        await runCacheAction("captcha-phrase", `&ip=${CURRENT_IP}`);
        const phrase = await page.$eval("h1", (el) => el.innerText);
        await publicHomepageShouldBeCaptchaWall();
        await fillByName("phrase", phrase);
        await page.locator('button:text("CONTINUE")').click();
        await publicHomepageShouldBeAccessible();
        // Clear cache for next tests
        await runCacheAction("clear");
    });

    it("Should display a ban wall", async () => {
        await banIpForSeconds(15 * 60, CURRENT_IP);
        await publicHomepageShouldBeBanWall();
    });

    it("Should display a captcha wall instead of a ban wall in Flex mode", async () => {
        await goToSettingsPage();
        await selectElement(
            "crowdsec_bouncer_general_bouncing_level",
            "flex_bouncing",
        );
        await onAdminSaveSettings();
        await publicHomepageShouldBeCaptchaWall();

        // Reset to default value
        await goToSettingsPage();
        await selectElement(
            "crowdsec_bouncer_general_bouncing_level",
            "normal_bouncing",
        );
        await onAdminSaveSettings();
    });

    it("Should display back the homepage with no remediation", async () => {
        await removeAllDecisions();
        await publicHomepageShouldBeAccessible();
    });

    it("Should display ban wall on Admin", async () => {
        await goToSettingsPage();
        await selectElement(
            "crowdsec_bouncer_general_bouncing_admin_enabled",
            "1",
        );
        await onAdminSaveSettings();
        await adminPageShouldBeAccessible();
        await banIpForSeconds(15 * 60, CURRENT_IP);
        await adminPageShouldBeBanWall();
        // Reset to default value
        await removeAllDecisions();
        await goToSettingsPage();
        await selectElement(
            "crowdsec_bouncer_general_bouncing_admin_enabled",
            "0",
        );
        await onAdminSaveSettings();
    });

    it("Should fallback to the selected remediation for unknown remediation", async () => {
        await removeAllDecisions();
        await addDecision(CURRENT_IP, "mfa", 15 * 60);
        await wait(1000);
        await goToSettingsPage();
        await selectElement(
            "crowdsec_bouncer_advanced_remediation_fallback",
            "captcha",
        );
        await onAdminSaveSettings();
        await publicHomepageShouldBeCaptchaWall();
        await goToSettingsPage();
        // Reset to default
        await selectElement(
            "crowdsec_bouncer_advanced_remediation_fallback",
            "bypass",
        );
        await onAdminSaveSettings();
        await publicHomepageShouldBeAccessible();
    });

    it("Should handle X-Forwarded-For header for whitelisted IPs only", async () => {
        await removeAllDecisions();
        await banIpForSeconds(15 * 60, CURRENT_IP);

        // Remove the PROXY IP from the CDN list (via a range)
        await goToSettingsPage();
        await fillInput(
            "crowdsec_bouncer_advanced_remediation_trust_ip_forward_list",
            "",
        );
        await onAdminSaveSettings();

        // Should not be banned as router IP is not trust by CDN
        await publicHomepageShouldBeAccessible();

        // Reset the PROXY IP from the CDN list (via a range)
        await goToSettingsPage();
        await fillInput(
            "crowdsec_bouncer_advanced_remediation_trust_ip_forward_list",
            PROXY_IP,
        );
        await onAdminSaveSettings();

        // Should be banned as router IP is trust by CDN
        await publicHomepageShouldBeBanWall();
    });
});

describe(`Test cURL in Live mode`, () => {
    it("Should configure curl", async () => {
        await goToSettingsPage();
        await selectElement(
            "crowdsec_bouncer_general_connection_use_curl",
            "1",
        );
        await page.click("#crowdsec_bouncer_general_connection_test");
        await expect(page).toMatchText(
            "#lapi_ping_result",
            /Connection test result: success.*Use cURL: true/,
        );
        await onAdminSaveSettings();
    });
    it("Should display the homepage with no remediation", async () => {
        await removeAllDecisions();
        await publicHomepageShouldBeAccessible();
    });
    it("Should display a ban wall", async () => {
        await banIpForSeconds(15 * 60, CURRENT_IP);
        await publicHomepageShouldBeBanWall();
    });
});

describe(`Test TLS auth in Live mode`, () => {
    it("Should configure TLS", async () => {
        await goToSettingsPage();
        await selectElement(
            "crowdsec_bouncer_general_connection_use_curl",
            "1",
        );
        await selectElement(
            "crowdsec_bouncer_general_connection_auth_type",
            "tls",
        );

        await fillInput(
            "crowdsec_bouncer_general_connection_tls_key_path",
            `${TLS_PATH}/${BOUNCER_KEY_FILE}`,
        );
        await selectElement(
            "crowdsec_bouncer_general_connection_tls_verify_peer",
            "1",
        );
        await fillInput(
            "crowdsec_bouncer_general_connection_tls_ca_cert_path",
            `${TLS_PATH}/${CA_CERT_FILE}`,
        );
        // Bad path
        await fillInput(
            "crowdsec_bouncer_general_connection_tls_cert_path",
            "bad-path",
        );

        await page.click("#crowdsec_bouncer_general_connection_test");
        await wait(2000);
        await expect(page).toMatchText(
            "#lapi_ping_result",
            /Technical error.*could not load PEM client certificate/,
        );

        // Bad cert
        await fillInput(
            "crowdsec_bouncer_general_connection_tls_cert_path",
            `${TLS_PATH}/${AGENT_CERT_FILE}`,
        );

        await page.click("#crowdsec_bouncer_general_connection_test");
        await wait(2000);
        await expect(page).toMatchText(
            "#lapi_ping_result",
            /Technical error.*unable to set private key file/,
        );

        // Bad CA with verify peer
        await fillInput(
            "crowdsec_bouncer_general_connection_tls_cert_path",
            `${TLS_PATH}/${BOUNCER_CERT_FILE}`,
        );
        await fillInput(
            "crowdsec_bouncer_general_connection_tls_ca_cert_path",
            `${TLS_PATH}/${AGENT_CERT_FILE}`,
        );

        await page.click("#crowdsec_bouncer_general_connection_test");
        await wait(2000);
        await expect(page).toMatchText(
            "#lapi_ping_result",
            /Technical error.*unable to get local issuer certificate/,
        );

        // Bad CA without verify peer
        await selectElement(
            "crowdsec_bouncer_general_connection_tls_verify_peer",
            "0",
        );

        await page.click("#crowdsec_bouncer_general_connection_test");
        await wait(2000);
        await expect(page).toMatchText(
            "#lapi_ping_result",
            /Connection test result: success.*Auth type: TLS.*Use cURL: true/,
        );

        // Good settings with curl
        await selectElement(
            "crowdsec_bouncer_general_connection_tls_verify_peer",
            "1",
        );
        await fillInput(
            "crowdsec_bouncer_general_connection_tls_ca_cert_path",
            `${TLS_PATH}/${CA_CERT_FILE}`,
        );
        await fillInput(
            "crowdsec_bouncer_general_connection_tls_cert_path",
            `${TLS_PATH}/${BOUNCER_CERT_FILE}`,
        );

        await page.click("#crowdsec_bouncer_general_connection_test");
        await wait(2000);

        await expect(page).toMatchText(
            "#lapi_ping_result",
            /Connection test result: success.*Auth type: TLS.*Use cURL: true/,
        );

        // Good settings without curl
        await selectElement(
            "crowdsec_bouncer_general_connection_use_curl",
            "0",
        );
        await page.click("#crowdsec_bouncer_general_connection_test");
        await wait(2000);

        await expect(page).toMatchText(
            "#lapi_ping_result",
            /Connection test result: success.*Auth type: TLS.*Use cURL: false/,
        );

        await onAdminSaveSettings();
    });

    it("Should display the homepage with no remediation", async () => {
        await removeAllDecisions();
        await publicHomepageShouldBeAccessible();
    });

    it("Should display a ban wall", async () => {
        await banIpForSeconds(15 * 60, CURRENT_IP);
        await publicHomepageShouldBeBanWall();
    });
});

describe(`Test cache in Live mode`, () => {
    it("Should configure the cache", async () => {
        await goToSettingsPage();
        await fillInput(
            "crowdsec_bouncer_advanced_cache_clean_ip_cache_duration",
            30,
        );
        await fillInput(
            "crowdsec_bouncer_advanced_cache_bad_ip_cache_duration",
            30,
        );
        await onAdminSaveSettings();
    });
    it("Should clear the cache on demand", async () => {
        await banIpForSeconds(15 * 60, CURRENT_IP);
        await publicHomepageShouldBeBanWall();
        await wait(2000);
        await publicHomepageShouldBeBanWall();
        await removeAllDecisions();
        await wait(2000);
        await publicHomepageShouldBeBanWall();
        await goToSettingsPage();
        await page.click("#crowdsec_bouncer_advanced_cache_clear_cache");
        await expect(page).toMatchText(
            "#cache_clearing_result",
            /CrowdSec cache \(.*\) has been cleared./,
        );
        await publicHomepageShouldBeAccessible();
    });
    it("Should log miss then hit", async () => {
        await goToSettingsPage();
        await page.click("#crowdsec_bouncer_advanced_cache_clear_cache");
        await expect(page).toMatchText(
            "#cache_clearing_result",
            /CrowdSec cache \(.*\) has been cleared./,
        );
        await deleteFileContent(DEBUG_LOG_PATH);
        let logContent = await getFileContent(DEBUG_LOG_PATH);
        await expect(logContent).toBe("");
        await goToPublicPage();
        logContent = await getFileContent(DEBUG_LOG_PATH);
        await expect(logContent).toMatch(
            new RegExp(
                `{"type":"LAPI_REM_CACHED_DECISIONS","ip":"${CURRENT_IP}","result":"miss"}`,
            ),
        );
        await deleteFileContent(DEBUG_LOG_PATH);
        await wait(1000);
        await goToPublicPage();
        logContent = await getFileContent(DEBUG_LOG_PATH);
        await expect(logContent).toMatch(
            new RegExp(
                `{"type":"LAPI_REM_CACHED_DECISIONS","ip":"${CURRENT_IP}","result":"hit"}`,
            ),
        );
    });
});
