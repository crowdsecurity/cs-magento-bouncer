/* eslint-disable no-undef */
const { CURRENT_IP } = require("../utils/constants");

const {
    notify,
    publicHomepageShouldBeBanWall,
    publicHomepageShouldBeCaptchaWallWithMentions,
    publicHomepageShouldBeAccessible,
    banIpForSeconds,
    captchaIpForSeconds,
    loadCookies,
    removeAllDecisions,
    selectElement,
    onAdminSaveSettings,
    onAdminGoToSettingsPage,
    onLoginPageLoginAsAdmin,
    goToAdmin,
    storeCookies,
    onAdminFlushCache,
} = require("../utils/helpers");

describe(`Configure Live mode`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    it("Should login to M2 admin", async () => {
        // "Login" page
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await storeCookies();
    });

    it("Should go on CrowdSec Bouncer section", async () => {
        // "CrowdSec Bouncer" page
        await onAdminGoToSettingsPage();
    });

    it("Should configure the live mode", async () => {
        await selectElement("crowdsec_bouncer_advanced_mode_stream", "0");
    });

    it("Should save settings", async () => {
        await onAdminSaveSettings();
    });

    it("Should flush the cache", async () => {
        await onAdminFlushCache();
    });
});

describe(`Run in Live mode`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    beforeAll(async () => {
        await loadCookies(context);
        await removeAllDecisions();
    });

    it('Should display the homepage with no remediation"', async () => {
        await publicHomepageShouldBeAccessible();
    });

    it('Should display a captcha wall"', async () => {
        await captchaIpForSeconds(15 * 60, CURRENT_IP);
        await publicHomepageShouldBeCaptchaWallWithMentions();
    });

    it('Should display a ban wall"', async () => {
        await banIpForSeconds(15 * 60, CURRENT_IP);
        await publicHomepageShouldBeBanWall();
    });
});
