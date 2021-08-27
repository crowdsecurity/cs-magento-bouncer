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
    onAdminFlushCache,
    fillInput,
    goToSettingsPage,
    waitForNavigation,
    forceCronRun,
} = require("../utils/helpers");

const { addDecision } = require("../utils/watcherClient");

describe(`Configure Stream mode`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    beforeAll(async () => {
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await storeCookies();
        await onAdminFlushCache();
        await removeAllDecisions();
    });

    it("Should go on CrowdSec Bouncer section and prepare configs", async () => {
        // "CrowdSec Bouncer" page
        await goToSettingsPage();
        await selectElement("crowdsec_bouncer_advanced_mode_stream", "0");
        await onAdminSaveSettings();
    });

    it("Should not save bad settings", async () => {
        await selectElement("crowdsec_bouncer_advanced_mode_stream", "1");
        await fillInput(
            "crowdsec_bouncer_advanced_mode_refresh_cron_expr",
            "bad",
        );
        await onAdminSaveSettings();
        await expect(page).toMatchText(
            "#messages .messages .message:nth-of-type(1)",
            "Refresh expression cron (bad) is not valid.",
        );
        await expect(page).toMatchValue(
            "#crowdsec_bouncer_advanced_mode_stream",
            "0",
        );
    });

    it("Should save good settings", async () => {
        await selectElement("crowdsec_bouncer_advanced_mode_stream", "1");
        await fillInput(
            "crowdsec_bouncer_advanced_mode_refresh_cron_expr",
            "* * * * *",
        );
        await onAdminSaveSettings();
        await expect(page).toMatchText(
            "#messages .messages .message:nth-of-type(1)",
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
        await forceCronRun();
        await publicHomepageShouldBeBanWall();
    });

    it("Should display back the homepage with no remediation via stream mode", async () => {
        await removeAllDecisions();
        await forceCronRun();
        await publicHomepageShouldBeAccessible();
    });

    it("Should retrieve new decision on refresh", async () => {
        await removeAllDecisions();
        await goToSettingsPage();
        await addDecision(CURRENT_IP, "ban", 15 * 60);
        await page.click("#crowdsec_bouncer_advanced_mode_refresh_cache");
        await expect(page).toMatchText(
            "#cache_refresh_result",
            /CrowdSec cache \(.*\) has been refreshed. New decision\(s\): 1. Deleted decision\(s\): .*/,
        );
    });
});
