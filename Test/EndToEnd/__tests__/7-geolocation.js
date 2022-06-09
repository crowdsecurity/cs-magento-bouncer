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
            "crowdsec_bouncer_advanced_geolocation_save_result",
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
        await page.click("#crowdsec_bouncer_advanced_geolocation_geolocalize");
        await expect(page).toMatchText(
            "#geolocation_test_result",
            /Geolocation test result: success./,
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

    it("Should call or not call the GeoIp database depending on save result config", async () => {
        await goToSettingsPage(true);

        // Do not save result
        await selectElement(
            "crowdsec_bouncer_advanced_geolocation_save_result",
            "0",
        );

        // Set a good path to simulate bad database
        await fillInput(
            "crowdsec_bouncer_advanced_geolocation_maxmind_database_path",
            "crowdsec/GeoLite2-City.mmdb",
        );
        await page.click("#crowdsec_bouncer_advanced_geolocation_geolocalize");
        await wait(2000);
        await expect(page).toMatchText(
            "#geolocation_test_result",
            /Geolocation test result: success./,
        );
        // Set a bad path to simulate bad database
        await fillInput(
            "crowdsec_bouncer_advanced_geolocation_maxmind_database_path",
            "crowdsec/GeoLite2-FAKE.mmdb",
        );

        await page.click("#crowdsec_bouncer_advanced_geolocation_geolocalize");
        await wait(2000);
        await expect(page).toMatchText(
            "#geolocation_test_result",
            /Geolocation test result: failed./,
        );

        // Save result
        await selectElement(
            "crowdsec_bouncer_advanced_geolocation_save_result",
            "1",
        );

        // Set a good path to simulate bad database
        await fillInput(
            "crowdsec_bouncer_advanced_geolocation_maxmind_database_path",
            "crowdsec/GeoLite2-City.mmdb",
        );
        await page.click("#crowdsec_bouncer_advanced_geolocation_geolocalize");
        await wait(2000);
        await expect(page).toMatchText(
            "#geolocation_test_result",
            /Geolocation test result: success./,
        );
        // Set a bad path to simulate bad database
        await fillInput(
            "crowdsec_bouncer_advanced_geolocation_maxmind_database_path",
            "crowdsec/GeoLite2-FAKE.mmdb",
        );

        await page.click("#crowdsec_bouncer_advanced_geolocation_geolocalize");
        await wait(2000);
        // Should not call the database
        await expect(page).toMatchText(
            "#geolocation_test_result",
            /Geolocation test result: success./,
        );
    });
});
