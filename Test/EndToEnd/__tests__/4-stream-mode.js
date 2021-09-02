/* eslint-disable no-undef */
const { CURRENT_IP } = require("../utils/constants");

const {
    notify,
    publicHomepageShouldBeBanWall,
    publicHomepageShouldBeAccessible,
    banIpForSeconds,
    loadCookies,
    removeAllDecisions,
    selectElement,
    onAdminSaveSettings,
    onLoginPageLoginAsAdmin,
    goToAdmin,
    storeCookies,
    fillInput,
    goToSettingsPage,
    runCron,
} = require("../utils/helpers");

describe(`Configure Stream mode`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    beforeAll(async () => {
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await storeCookies();
        await removeAllDecisions();
    });

    it("Should go on CrowdSec Bouncer section and prepare configs", async () => {
        // "CrowdSec Bouncer" page
        await goToSettingsPage();
        await selectElement("crowdsec_bouncer_advanced_mode_stream", "0");
        await onAdminSaveSettings();
    });

    it("Should save good settings", async () => {
        await selectElement("crowdsec_bouncer_advanced_mode_stream", "1");
        await fillInput(
            "crowdsec_bouncer_advanced_mode_refresh_cron_expr",
            "* * * * *",
        );
        await onAdminSaveSettings();
        await expect(page).toMatchText(
            "#messages",
            /As the stream mode is enabled, the cache \(.*\) has been warmed up. There is now 0 decision in cache./,
        );
    });
});

describe(`Run in stream mode`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    beforeAll(async () => {
        await loadCookies(context);
    });

    it("Should display a ban wall via stream mode", async () => {
        await banIpForSeconds(15 * 60, CURRENT_IP);
        await runCron("CrowdSec\\Bouncer\\Cron\\RefreshCache");
        await publicHomepageShouldBeBanWall();
    });

    it("Should display back the homepage with no remediation via stream mode", async () => {
        await removeAllDecisions();
        await runCron("CrowdSec\\Bouncer\\Cron\\RefreshCache");
        await publicHomepageShouldBeAccessible();
    });

    it("Should retrieve new decision on refresh", async () => {
        await removeAllDecisions();
        await goToSettingsPage();
        await banIpForSeconds(15 * 60, CURRENT_IP);
        await page.click("#crowdsec_bouncer_advanced_mode_refresh_cache");
        await expect(page).toMatchText(
            "#cache_refresh_result",
            /CrowdSec cache \(.*\) has been refreshed. New decision\(s\): 1. Deleted decision\(s\): .*/,
        );
    });

    it("Should reset to Live Mode", async () => {
        await removeAllDecisions();
        await goToSettingsPage();
        await selectElement("crowdsec_bouncer_advanced_mode_stream", "0");
        await onAdminSaveSettings();
    });
});
