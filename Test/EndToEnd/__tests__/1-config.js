/* eslint-disable no-undef */
const {
    goToAdmin,
    onLoginPageLoginAsAdmin,
    fillInput,
    selectElement,
    onAdminSaveSettings,
    flushCache,
    setDefaultConfig,
    wait,
} = require("../utils/helpers");

const { BOUNCER_KEY } = require("../utils/constants");

describe(`Extension configuration`, () => {
    it("Should login to M2 admin", async () => {
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
    });

    it("Should set default config", async () => {
        await setDefaultConfig(true, false);
    });

    it("Should test connection with error", async () => {
        await fillInput(
            "crowdsec_bouncer_general_connection_api_key",
            "bad-key",
        );
        await page.click("#crowdsec_bouncer_general_connection_test");
        await expect(page).toMatchText(
            "#lapi_ping_result",
            /Technical error while testing connection/,
        );
        await fillInput(
            "crowdsec_bouncer_general_connection_api_key",
            BOUNCER_KEY,
        );
    });
});

describe(`Extension configuration modification`, () => {
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
        await fillInput("crowdsec_bouncer_advanced_cache_redis_dsn", "bad-dsn");
        await onAdminSaveSettings(false);
        await wait(3000);
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
        await wait(3000);
        await expect(page).toMatchText(
            "#messages",
            /Technical error while testing the Memcached cache/,
        );
        await expect(page).toMatchValue(
            "#crowdsec_bouncer_advanced_cache_memcached_dsn",
            "memcached://memcached:11211",
        );
    });

    it("Should flush the cache", async () => {
        await flushCache();
    });
});
