/* eslint-disable no-undef */
const {
    notify,
    goToAdmin,
    onAdminGoToSettingsPage,
    onLoginPageLoginAsAdmin,
    storeCookies,
    fillInput,
    selectElement,
    onAdminSaveSettings,
    onAdminFlushCache,
} = require("../utils/helpers");

const {
    LAPI_URL_FROM_M2,
    BOUNCER_KEY,
    PROXY_IP,
} = require("../utils/constants");

const waitForNavigation = page.waitForNavigation();

describe(`Set extension default configuration`, () => {
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

    it("Should configure the connection details", async () => {
        await fillInput(
            "crowdsec_bouncer_general_connection_api_url",
            LAPI_URL_FROM_M2,
        );
        await fillInput(
            "crowdsec_bouncer_general_connection_api_key",
            BOUNCER_KEY,
        );
    });

    it("Should test connection with success", async () => {
        await page.waitForSelector("#crowdsec_bouncer_general_connection_test");
        await page.click("#crowdsec_bouncer_general_connection_test");
        await waitForNavigation;
        await expect(page).toHaveText(
            "#lapi_ping_result",
            "Connection test result: success.",
        );
    });

    it("Should configure the live mode", async () => {
        await selectElement("crowdsec_bouncer_advanced_mode_stream", "0");
    });

    it("Should configure the cache", async () => {
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
        await page.waitForSelector("#messages");
        await expect(page).toHaveText(
            "#messages",
            "You saved the configuration.",
        );
    });
});

describe(`Modify extension configuration`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    it("Should go on CrowdSec Bouncer section", async () => {
        // "CrowdSec Bouncer" page
        await onAdminGoToSettingsPage();
    });

    it("Should configure the cache", async () => {
        // Test File System
        await selectElement(
            "crowdsec_bouncer_advanced_cache_technology",
            "phpfs",
        );
        await onAdminSaveSettings();
        await page.waitForSelector("#messages");
        await expect(page).toHaveText(
            "#messages",
            "CrowdSec new cache (File system) has been successfully tested.",
        );
        await page.waitForSelector(
            "#crowdsec_bouncer_advanced_cache_clear_cache",
        );
        await page.click("#crowdsec_bouncer_advanced_cache_clear_cache");
        await waitForNavigation;
        await expect(page).toHaveText(
            "#cache_clearing_result",
            "CrowdSec cache (File system) has been cleared.",
        );
        await page.click("#crowdsec_bouncer_advanced_cache_prune_cache");
        await waitForNavigation;
        await expect(page).toHaveText(
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
        await page.waitForSelector("#messages");
        await expect(page).toHaveText(
            "#messages",
            "CrowdSec new cache (Redis) has been successfully tested.",
        );
        await expect(page).toHaveText(
            "#messages",
            "File system cache has been cleared.",
        );
        await page.click("#crowdsec_bouncer_advanced_cache_clear_cache");
        await waitForNavigation;
        await expect(page).toHaveText(
            "#cache_clearing_result",
            "CrowdSec cache (Redis) has been cleared.",
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
        await page.waitForSelector("#messages");
        await expect(page).toHaveText(
            "#messages",
            "CrowdSec new cache (Memcached) has been successfully tested.",
        );
        await expect(page).toHaveText(
            "#messages",
            "Redis cache has been cleared.",
        );
        await page.click("#crowdsec_bouncer_advanced_cache_clear_cache");
        await waitForNavigation;
        await expect(page).toHaveText(
            "#cache_clearing_result",
            "CrowdSec cache (Memcached) has been cleared.",
        );
    });

    it("Should save settings", async () => {
        await onAdminSaveSettings();
        await page.waitForSelector("#messages");
        await expect(page).toHaveText(
            "#messages",
            "You saved the configuration.",
        );
    });
});

describe(`Flush the cache`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    it("Should flush the cache", async () => {
        await onAdminFlushCache();
        await page.waitForSelector("#messages");
        await expect(page).toHaveText(
            "#messages",
            "The Magento cache storage has been flushed.",
        );
    });
});
