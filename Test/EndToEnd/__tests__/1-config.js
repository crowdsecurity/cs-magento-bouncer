/* eslint-disable no-undef */
const {
    notify,
    goToAdmin,
    goToSettingsPage,
    onLoginPageLoginAsAdmin,
    storeCookies,
    fillInput,
    selectElement,
    onAdminSaveSettings,
    flushCache,
} = require("../utils/helpers");

const {
    LAPI_URL_FROM_M2,
    BOUNCER_KEY,
    PROXY_IP,
} = require("../utils/constants");

describe(`Extension configuration`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    it("Should login to M2 admin", async () => {
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await storeCookies();
    });

    it("Should go on CrowdSec Bouncer section", async () => {
        await goToSettingsPage();
    });

    it("Should open each settings tab", async () => {
        let visible = await page.isVisible("#crowdsec_bouncer_general");
        if (!visible) {
            await page.click("#crowdsec_bouncer_general-head");
        }
        visible = await page.isVisible("#crowdsec_bouncer_theme");
        if (!visible) {
            await page.click("#crowdsec_bouncer_theme-head");
        }
        visible = await page.isVisible("#crowdsec_bouncer_advanced");
        if (!visible) {
            await page.click("#crowdsec_bouncer_advanced-head");
        }
        visible = await page.isVisible("#crowdsec_bouncer_general");
        expect(visible).toBeTruthy();
        visible = await page.isVisible("#crowdsec_bouncer_theme");
        expect(visible).toBeTruthy();
        visible = await page.isVisible("#crowdsec_bouncer_advanced");
        expect(visible).toBeTruthy();
    });

    it("Should test connection with error", async () => {
        await fillInput(
            "crowdsec_bouncer_general_connection_api_url",
            LAPI_URL_FROM_M2,
        );

        await fillInput(
            "crowdsec_bouncer_general_connection_api_key",
            "bad-key",
        );
        await page.click("#crowdsec_bouncer_general_connection_test");
        await expect(page).toMatchText(
            "#lapi_ping_result",
            /Technical error while testing connection/,
        );
    });

    it("Should test connection with success", async () => {
        await fillInput(
            "crowdsec_bouncer_general_connection_api_url",
            LAPI_URL_FROM_M2,
        );

        await fillInput(
            "crowdsec_bouncer_general_connection_api_key",
            BOUNCER_KEY,
        );
        await page.click("#crowdsec_bouncer_general_connection_test");
        await expect(page).toMatchText(
            "#lapi_ping_result",
            /Connection test result: success./,
        );
    });

    it("Should configure bouncing", async () => {
        await selectElement(
            "crowdsec_bouncer_general_bouncing_front_enabled",
            "1",
        );
    });

    it("Should configure the live mode", async () => {
        await selectElement("crowdsec_bouncer_advanced_mode_stream", "0");
    });

    it("Should configure the cache", async () => {
        await selectElement(
            "crowdsec_bouncer_advanced_cache_technology",
            "redis",
        );
        await fillInput(
            "crowdsec_bouncer_advanced_cache_redis_dsn",
            "redis://redis:6379",
        );
        await fillInput(
            "crowdsec_bouncer_advanced_cache_clean_ip_cache_duration",
            1,
        );
        await fillInput(
            "crowdsec_bouncer_advanced_cache_bad_ip_cache_duration",
            1,
        );
    });

    it("Should configure the remediation", async () => {
        await fillInput(
            "crowdsec_bouncer_advanced_remediation_trust_ip_forward_list",
            PROXY_IP,
        );
    });

    it("Should save settings", async () => {
        await onAdminSaveSettings();
    });
});

describe(`Extension configuration modification`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    it("Should modify the cache", async () => {
        // Test File System
        await selectElement(
            "crowdsec_bouncer_advanced_cache_technology",
            "phpfs",
        );
        await onAdminSaveSettings();
        await expect(page).toMatchText(
            "#messages",
            /CrowdSec new cache \(File system\) has been successfully tested./,
        );
        await page.waitForSelector(
            "#crowdsec_bouncer_advanced_cache_clear_cache",
        );
        await page.click("#crowdsec_bouncer_advanced_cache_clear_cache");
        await expect(page).toMatchText(
            "#cache_clearing_result",
            "CrowdSec cache (File system) has been cleared.",
        );
        await page.click("#crowdsec_bouncer_advanced_cache_prune_cache");
        await expect(page).toMatchText(
            "#cache_pruning_result",
            "CrowdSec cache (File system) has been pruned.",
        );
        // Test Redis
        await selectElement(
            "crowdsec_bouncer_advanced_cache_technology",
            "redis",
        );
        await fillInput(
            "crowdsec_bouncer_advanced_cache_redis_dsn",
            "redis://redis:6379",
        );
        await onAdminSaveSettings();
        await expect(page).toMatchText(
            "#messages",
            /CrowdSec new cache \(Redis\) has been successfully tested./,
        );
        await expect(page).toMatchText(
            "#messages",
            /File system cache has been cleared./,
        );
        await page.click("#crowdsec_bouncer_advanced_cache_clear_cache");
        await expect(page).toMatchText(
            "#cache_clearing_result",
            "CrowdSec cache (Redis) has been cleared.",
        );
        await fillInput("crowdsec_bouncer_advanced_cache_redis_dsn", "bad-dns");
        await onAdminSaveSettings(false);
        await expect(page).toMatchText(
            "#messages",
            /Technical error while testing the Redis cache/,
        );
        await expect(page).toMatchValue(
            "#crowdsec_bouncer_advanced_cache_redis_dsn",
            "redis://redis:6379",
        );
        // Test Memcached
        await selectElement(
            "crowdsec_bouncer_advanced_cache_technology",
            "memcached",
        );
        await fillInput(
            "crowdsec_bouncer_advanced_cache_memcached_dsn",
            "memcached://memcached:11211",
        );
        await onAdminSaveSettings();
        await expect(page).toMatchText(
            "#messages",
            /CrowdSec new cache \(Memcached\) has been successfully tested./,
        );
        await expect(page).toMatchText(
            "#messages",
            /Redis cache has been cleared./,
        );
        await page.click("#crowdsec_bouncer_advanced_cache_clear_cache");
        await expect(page).toMatchText(
            "#cache_clearing_result",
            "CrowdSec cache (Memcached) has been cleared.",
        );
        await fillInput(
            "crowdsec_bouncer_advanced_cache_memcached_dsn",
            "memcached://memcached:18579",
        );
        await onAdminSaveSettings(false);
        await expect(page).toMatchText(
            "#messages",
            /Technical error while testing the Memcached cache/,
        );
        await expect(page).toMatchValue(
            "#crowdsec_bouncer_advanced_cache_memcached_dsn",
            "memcached://memcached:11211",
        );
    });

    it("Should save settings", async () => {
        await onAdminSaveSettings();
    });

    it("Should flush the cache", async () => {
        await flushCache();
    });
});
