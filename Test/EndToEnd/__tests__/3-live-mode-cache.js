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
    wait,
    fillInput,
    goToSettingsPage,
} = require("../utils/helpers");

describe(`Configure Live mode`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    beforeAll(async () => {
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await storeCookies();
        await onAdminFlushCache();
        await removeAllDecisions();
    });

    it("Should go on CrowdSec Bouncer section", async () => {
        // "CrowdSec Bouncer" page
        await goToSettingsPage();
    });

    it("Should configure the live mode", async () => {
        await selectElement("crowdsec_bouncer_advanced_mode_stream", "0");
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

describe(`Test cache in Live mode`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    beforeAll(async () => {
        await loadCookies(context);
        await removeAllDecisions();
    });

    it("Should configure the cache", async () => {
        await goToSettingsPage();

        await fillInput(
            "crowdsec_bouncer_advanced_cache_clean_ip_cache_duration",
            60,
        );
        await fillInput(
            "crowdsec_bouncer_advanced_cache_bad_ip_cache_duration",
            60,
        );
        await onAdminSaveSettings();
        await onAdminFlushCache();
    });

    it("Should clear the cache on demand", async () => {
        await banIpForSeconds(15 * 60, CURRENT_IP);
        await publicHomepageShouldBeBanWall();
        wait(2000);
        await publicHomepageShouldBeBanWall();
        await removeAllDecisions();
        wait(2000);
        await publicHomepageShouldBeBanWall();
        await goToSettingsPage();
        await page.click("#crowdsec_bouncer_advanced_cache_clear_cache");
        await expect(page).toMatchText(
            "#cache_clearing_result",
            /CrowdSec cache \(.*\) has been cleared./,
        );

        await publicHomepageShouldBeAccessible();
    });
});
