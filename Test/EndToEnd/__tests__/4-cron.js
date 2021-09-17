/* eslint-disable no-undef */

const {
    removeAllDecisions,
    selectElement,
    onAdminSaveSettings,
    onLoginPageLoginAsAdmin,
    goToAdmin,
    fillInput,
    goToSettingsPage,
    runCron,
    deleteFileContent,
    getFileContent,
    setDefaultConfig,
} = require("../utils/helpers");

const { DEBUG_LOG_PATH } = require("../utils/constants");

describe(`Configure and run crons`, () => {
    beforeAll(async () => {
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await removeAllDecisions();
        await setDefaultConfig(false);
    });

    it("Should go on CrowdSec Bouncer section and prepare configurations", async () => {
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
        await expect(logContent).toBe("");
        await runCron("CrowdSec\\Bouncer\\Cron\\PruneCache");
        logContent = await getFileContent(DEBUG_LOG_PATH);
        await expect(logContent).toMatch("CACHE_PRUNED");
    });

    it("Should log that refresh cron is running", async () => {
        await deleteFileContent(DEBUG_LOG_PATH);
        let logContent = await getFileContent(DEBUG_LOG_PATH);
        await expect(logContent).toBe("");
        await runCron("CrowdSec\\Bouncer\\Cron\\RefreshCache");
        logContent = await getFileContent(DEBUG_LOG_PATH);
        await expect(logContent).toMatch("CACHE_UPDATED");
    });
});
