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
    deleteFileContent,
    getFileContent,
} = require("../utils/helpers");

const { DEBUG_LOG_PATH } = require("../utils/constants");

describe(`Configure crons`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    beforeAll(async () => {
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await storeCookies();
        await removeAllDecisions();
    });

    it("Should go on CrowdSec Bouncer section and prepare configs", async () => {
        await goToSettingsPage();
        await selectElement("crowdsec_bouncer_advanced_mode_stream", "1");
        await fillInput(
            "crowdsec_bouncer_advanced_mode_refresh_cron_expr",
            "* * * * *",
        );
        await selectElement(
            "crowdsec_bouncer_advanced_cache_technology",
            "phpfs",
        );
        await fillInput(
            "crowdsec_bouncer_advanced_cache_prune_cron_expr",
            "* * * * *",
        );
        await onAdminSaveSettings();
    });

    it("Should not save bad pruning settings", async () => {
        await fillInput(
            "crowdsec_bouncer_advanced_cache_prune_cron_expr",
            "bad",
        );
        await onAdminSaveSettings(false);
        await expect(page).toMatchText(
            "#messages",
            /Pruning cron expression \(bad\) is not valid./,
        );
        await expect(page).toMatchValue(
            "#crowdsec_bouncer_advanced_cache_prune_cron_expr",
            "* * * * *",
        );
    });

    it("Should not save bad refresh settings", async () => {
        await selectElement("crowdsec_bouncer_advanced_mode_stream", "1");
        await fillInput(
            "crowdsec_bouncer_advanced_mode_refresh_cron_expr",
            "bad",
        );
        await onAdminSaveSettings(false);
        await expect(page).toMatchText(
            "#messages",
            /Refresh cron expression \(bad\) is not valid./,
        );
        await expect(page).toMatchValue(
            "#crowdsec_bouncer_advanced_mode_refresh_cron_expr",
            "* * * * *",
        );
    });

    it("Should log that pruning cron is running", async () => {
        await deleteFileContent(DEBUG_LOG_PATH);
        let logContent = await getFileContent(DEBUG_LOG_PATH);
        expect(logContent).toBe("");
        await runCron("CrowdSec\\Bouncer\\Cron\\PruneCache");
        logContent = await getFileContent(DEBUG_LOG_PATH);
        expect(logContent).toMatch("CACHE_PRUNED");
    });

    it("Should log that refresh cron is running", async () => {
        await deleteFileContent(DEBUG_LOG_PATH);
        let logContent = await getFileContent(DEBUG_LOG_PATH);
        expect(logContent).toBe("");
        await runCron("CrowdSec\\Bouncer\\Cron\\RefreshCache");
        logContent = await getFileContent(DEBUG_LOG_PATH);
        expect(logContent).toMatch("CACHE_UPDATED");
    });
});
