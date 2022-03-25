/* eslint-disable no-undef */
const {
    removeAllDecisions,
    onLoginPageLoginAsAdmin,
    goToAdmin,
    setDefaultConfig,
    goToSettingsPage,
    deleteFileContent,
    getFileContent,
    fillInput,
    selectElement,
    onAdminSaveSettings,
    wait,
    flushCache,
    publicHomepageShouldBeBanWall,
    publicHomepageShouldBeCaptchaWallWithMentions,
    publicHomepageShouldBeAccessible,
} = require("../utils/helpers");

const { addDecision } = require("../utils/watcherClient");

const { DEBUG_LOG_PATH, FRANCE_IP, JAPAN_IP } = require("../utils/constants");

describe(`Geolocation and country scoped decision`, () => {
    beforeAll(async () => {
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await removeAllDecisions();
        await setDefaultConfig();
        await flushCache();
    });

    beforeEach(async () => {
        await deleteFileContent(DEBUG_LOG_PATH);
        const logContent = await getFileContent(DEBUG_LOG_PATH);
        await expect(logContent).toBe("");
    });

    it("Should retrieve good decisions with Country database", async () => {
        await goToSettingsPage(true);
        // Prepare Geolocation test config
        await fillInput(
            "crowdsec_bouncer_advanced_debug_forced_test_ip",
            FRANCE_IP,
        );
        await selectElement(
            "crowdsec_bouncer_advanced_geolocation_enabled",
            "1",
        );
        await selectElement(
            "crowdsec_bouncer_advanced_geolocation_save_in_session",
            "0",
        );
        await selectElement(
            "crowdsec_bouncer_advanced_geolocation_maxmind_database_type",
            "country",
        );
        await fillInput(
            "crowdsec_bouncer_advanced_geolocation_maxmind_database_path",
            "crowdsec/GeoLite2-Country.mmdb",
        );
        await onAdminSaveSettings();
        await addDecision("FR", "ban", 15 * 60, "Country");
        await wait(1000);
        await publicHomepageShouldBeBanWall();

        await removeAllDecisions();
        await addDecision("FR", "captcha", 15 * 60, "Country");
        await wait(1000);
        await publicHomepageShouldBeCaptchaWallWithMentions();

        await addDecision(FRANCE_IP, "ban", 15 * 60, "Ip");
        await wait(1000);
        await publicHomepageShouldBeBanWall();

        await removeAllDecisions();
        await addDecision("JP", "captcha", 15 * 60, "Country");
        await wait(1000);
        await publicHomepageShouldBeAccessible();
    });

    it("Should retrieve good decisions with City database", async () => {
        await goToSettingsPage(true);
        // Prepare Geolocation test config
        await selectElement(
            "crowdsec_bouncer_advanced_geolocation_maxmind_database_type",
            "city",
        );
        await fillInput(
            "crowdsec_bouncer_advanced_geolocation_maxmind_database_path",
            "crowdsec/GeoLite2-City.mmdb",
        );
        await onAdminSaveSettings();
        await addDecision("FR", "ban", 15 * 60, "Country");
        await wait(1000);
        await publicHomepageShouldBeBanWall();

        await removeAllDecisions();
        await addDecision("FR", "captcha", 15 * 60, "Country");
        await wait(1000);
        await publicHomepageShouldBeCaptchaWallWithMentions();

        await addDecision(FRANCE_IP, "ban", 15 * 60, "Ip");
        await wait(1000);
        await publicHomepageShouldBeBanWall();

        await removeAllDecisions();
        await addDecision("JP", "captcha", 15 * 60, "Country");
        await wait(1000);
        await publicHomepageShouldBeAccessible();
    });

    it("Should save geolocation country result in session", async () => {
        await goToSettingsPage(true);
        // Prepare Geolocation test config
        await selectElement(
            "crowdsec_bouncer_advanced_geolocation_save_in_session",
            "1",
        );
        await onAdminSaveSettings();
        await addDecision("FR", "ban", 15 * 60, "Country");
        await wait(1000);
        await publicHomepageShouldBeBanWall();

        await goToSettingsPage(true);
        await fillInput(
            "crowdsec_bouncer_advanced_debug_forced_test_ip",
            JAPAN_IP,
        );
        await onAdminSaveSettings();
        await deleteFileContent(DEBUG_LOG_PATH);
        let logContent = await getFileContent(DEBUG_LOG_PATH);
        await expect(logContent).toBe("");
        await publicHomepageShouldBeBanWall();

        logContent = await getFileContent(DEBUG_LOG_PATH);
        await expect(logContent).toMatch(
            new RegExp(
                `"type":"BAD_VALUE","value":"FR","scope":"Country","remediation":"ban"`,
            ),
        );
        await expect(logContent).toMatch(
            new RegExp(`"type":"FINAL_REMEDIATION","ip":"${JAPAN_IP}"`),
        );
    });
});
