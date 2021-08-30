/* eslint-disable no-undef */
const { CURRENT_IP, PROXY_IP } = require("../utils/constants");

const {
    notify,
    publicHomepageShouldBeBanWall,
    publicHomepageShouldBeCaptchaWallWithMentions,
    publicHomepageShouldBeCaptchaWallWithoutMentions,
    publicHomepageShouldBeAccessible,
    publicHomepageShouldBeCaptchaWall,
    adminPageShouldBeAccessible,
    adminPageShouldBeBanWall,
    banIpForSeconds,
    captchaIpForSeconds,
    loadCookies,
    removeAllDecisions,
    selectElement,
    onAdminSaveSettings,
    onLoginPageLoginAsAdmin,
    goToAdmin,
    goToSettingsPage,
    storeCookies,
    wait,
    fillInput,
} = require("../utils/helpers");
const { addDecision } = require("../utils/watcherClient");

describe(`Live mode configuration`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    beforeAll(async () => {
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await storeCookies();
        await removeAllDecisions();
    });

    it("Should go on CrowdSec Bouncer section", async () => {
        // "CrowdSec Bouncer" page
        await goToSettingsPage();
    });

    it("Should configure the live mode", async () => {
        await selectElement("crowdsec_bouncer_advanced_mode_stream", "0");
    });

    it("Should configure the cache duration", async () => {
        await fillInput(
            "crowdsec_bouncer_advanced_cache_clean_ip_cache_duration",
            1,
        );
        await fillInput(
            "crowdsec_bouncer_advanced_cache_bad_ip_cache_duration",
            1,
        );
    });

    it("Should configure the bouncing level mode", async () => {
        await selectElement(
            "crowdsec_bouncer_general_bouncing_level",
            "normal_bouncing",
        );
    });

    it("Should save settings", async () => {
        await onAdminSaveSettings();
    });
});

describe(`Live mode run`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    beforeAll(async () => {
        await loadCookies(context);
        await removeAllDecisions();
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
