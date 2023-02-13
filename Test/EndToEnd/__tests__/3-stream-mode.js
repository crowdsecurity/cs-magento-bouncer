/* eslint-disable no-undef */
const { CURRENT_IP } = require("../utils/constants");

const {
    publicHomepageShouldBeBanWall,
    publicHomepageShouldBeAccessible,
    banIpForSeconds,
    removeAllDecisions,
    selectElement,
    onAdminSaveSettings,
    onLoginPageLoginAsAdmin,
    goToAdmin,
    fillInput,
    goToSettingsPage,
    runCron,
    setDefaultConfig,
} = require("../utils/helpers");

describe(`Configure Stream mode`, () => {
    beforeAll(async () => {
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await removeAllDecisions();
        await setDefaultConfig();
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
            /As the stream mode is enabled, the cache \(.*\) has been refreshed. New decision\(s\): 0. Deleted decision\(s\): 0./,
        );
    });
});

describe(`Run in stream mode`, () => {
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
