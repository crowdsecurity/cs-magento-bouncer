/* eslint-disable no-undef */
const fs = require("fs");

const { addDecision, deleteAllDecisions } = require("./watcherClient");
const {
    ADMIN_URL,
    M2_URL,
    ADMIN_LOGIN,
    ADMIN_PASSWORD,
    TIMEOUT,
    LAPI_URL_FROM_M2,
    BOUNCER_KEY,
    PROXY_IP,
} = require("./constants");

const wait = async (ms) => new Promise((resolve) => setTimeout(resolve, ms));

jest.setTimeout(TIMEOUT);

const fillInput = async (optionId, value) => {
    await page.fill(`[id=${optionId}]`, `${value}`);
};
const fillByName = async (name, value) => {
    await page.fill(`[name=${name}]`, `${value}`);
};

const selectElement = async (selectId, valueToSelect) => {
    await page.selectOption(`[id=${selectId}]`, `${valueToSelect}`);
};

const selectByName = async (selectName, valueToSelect) => {
    await page.selectOption(`[name=${selectName}]`, `${valueToSelect}`);
};

const goToAdmin = async () => {
    await page.goto(ADMIN_URL, { waitUntil: "networkidle" });
};

const goToPublicPage = async (endpoint = "") => {
    return page.goto(`${M2_URL}${endpoint}`);
};

const onAdminGoToSettingsPage = async () => {
    await page.click("#menu-magento-backend-stores > a");
    await page.waitForLoadState("networkidle");
    await page.click(
        '#menu-magento-backend-stores .item-system-config:has-text("Configuration") ',
    );
    await page.waitForLoadState("networkidle");
    await wait(1000);

    await page.click(
        '#system_config_tabs .config-nav-block:has-text("Security")',
    );
    await page.waitForLoadState("networkidle");
    await wait(1000);
    await page.click('.config-nav-block li:has-text("CrowdSec Bouncer")');
    await page.waitForLoadState("networkidle");
    await expect(page).toMatchText(
        "#crowdsec_bouncer_general-head",
        "General settings",
    );
    // Open al tabs
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
    visible = await page.isVisible("#crowdsec_bouncer_events");
    if (!visible) {
        await page.click("#crowdsec_bouncer_events-head");
    }
    visible = await page.isVisible("#crowdsec_bouncer_general");
    await expect(visible).toBeTruthy();
    visible = await page.isVisible("#crowdsec_bouncer_theme");
    await expect(visible).toBeTruthy();
    visible = await page.isVisible("#crowdsec_bouncer_advanced");
    await expect(visible).toBeTruthy();
    visible = await page.isVisible("#crowdsec_bouncer_events");
    await expect(visible).toBeTruthy();
};

const onAdminSaveSettings = async (successExpected = true) => {
    await page.click("#save");
    await page.waitForLoadState("networkidle");
    if (successExpected) {
        await expect(page).toMatchText(
            "#messages",
            /You saved the configuration./,
        );
    }
};

const goToSettingsPage = async () => {
    await goToAdmin();
    await onAdminGoToSettingsPage();
};

const setDefaultConfig = async (save = true) => {
    await goToSettingsPage();
    // Connexion details
    await fillInput(
        "crowdsec_bouncer_general_connection_api_url",
        LAPI_URL_FROM_M2,
    );
    await fillInput("crowdsec_bouncer_general_connection_api_key", BOUNCER_KEY);
    await page.click("#crowdsec_bouncer_general_connection_test");
    await expect(page).toMatchText(
        "#lapi_ping_result",
        /Connection test result: success./,
    );
    // Bouncing normal
    await selectElement(
        "crowdsec_bouncer_general_bouncing_level",
        "normal_bouncing",
    );
    // Enable on front
    await selectElement("crowdsec_bouncer_general_bouncing_front_enabled", "1");
    // Disable on admin
    await selectElement("crowdsec_bouncer_general_bouncing_admin_enabled", "0");
    // Disable on API
    await selectElement("crowdsec_bouncer_general_bouncing_api_enabled", "0");
    // Live mode
    await selectElement("crowdsec_bouncer_advanced_mode_stream", "0");
    // Redis cache
    await selectElement("crowdsec_bouncer_advanced_cache_technology", "redis");
    await fillInput(
        "crowdsec_bouncer_advanced_cache_redis_dsn",
        "redis://redis:6379",
    );
    await fillInput(
        "crowdsec_bouncer_advanced_cache_clean_ip_cache_duration",
        1,
    );
    await fillInput("crowdsec_bouncer_advanced_cache_bad_ip_cache_duration", 1);
    // Remediation
    await selectElement(
        "crowdsec_bouncer_advanced_remediation_fallback",
        "bypass",
    );
    await fillInput(
        "crowdsec_bouncer_advanced_remediation_trust_ip_forward_list",
        PROXY_IP,
    );
    // Show mentions
    await selectElement(
        "crowdsec_bouncer_advanced_remediation_hide_mentions",
        "0",
    );
    // Debug
    await selectElement("crowdsec_bouncer_advanced_debug_log", "1");
    await selectElement("crowdsec_bouncer_advanced_debug_display_errors", "1");
    // Events
    await selectElement("crowdsec_bouncer_events_log_enabled", "1");
    await selectElement("crowdsec_bouncer_events_log_hide_sensitive", "0");

    if (save) {
        await onAdminSaveSettings();
    }
};

const flushCache = async () => {
    await goToAdmin();
    await page.click("#menu-magento-backend-system > a");
    await page.waitForLoadState("networkidle");

    await page.click(
        '#menu-magento-backend-system .item-system-cache:has-text("Cache Management") ',
    );
    await page.waitForLoadState("networkidle");
    await expect(page).toMatchTitle(/Cache Management/);
    await page.click("#flush_magento");
    await page.waitForLoadState("networkidle");
    await expect(page).toMatchText(
        "#messages",
        "The Magento cache storage has been flushed.",
    );
};

const runCron = async (cronClass) => {
    await page.goto(`${M2_URL}/cronLaunch.php?job=${cronClass}`);
    await page.waitForLoadState("networkidle");
    await expect(page).not.toMatchTitle(/404/);
    await expect(page).toMatchText("");
};

const onLoginPageLoginAsAdmin = async () => {
    await page.fill("#username", ADMIN_LOGIN);
    await page.fill("#login", ADMIN_PASSWORD);
    await page.click(".action-login");
    await page.waitForLoadState("networkidle");
    // On first login only, there is a modal to allow admin usage statistics
    adminUsage = await page.isVisible(".admin-usage-notification");
    if (adminUsage) {
        await page.click(".admin-usage-notification .action-secondary");
        await page.waitForLoadState("networkidle");
    }

    await expect(page).toMatchTitle(/Dashboard/);
};

const computeCurrentPageRemediation = async (
    accessibleTextInTitle = "Home page",
) => {
    const title = await page.title();
    if (title.includes(accessibleTextInTitle)) {
        return "bypass";
    }
    await expect(title).toContain("Oops");
    const description = await page.$eval(".desc", (el) => el.innerText);
    const banText = "cyber";
    const captchaText = "check";
    if (description.includes(banText)) {
        return "ban";
    }
    if (description.includes(captchaText)) {
        return "captcha";
    }

    throw Error("Current remediation can not be computed");
};

const publicHomepageShouldBeBanWall = async () => {
    await goToPublicPage();
    const remediation = await computeCurrentPageRemediation();
    await expect(remediation).toBe("ban");
};

const publicHomepageShouldBeCaptchaWall = async () => {
    await goToPublicPage();
    const remediation = await computeCurrentPageRemediation();
    await expect(remediation).toBe("captcha");
};

const publicHomepageShouldBeCaptchaWallWithoutMentions = async () => {
    await publicHomepageShouldBeCaptchaWall();
    await expect(page).not.toHaveText(
        ".main",
        "This security check has been powered by",
    );
};

const publicHomepageShouldBeCaptchaWallWithMentions = async () => {
    await publicHomepageShouldBeCaptchaWall();
    await expect(page).toHaveText(
        ".main",
        "This security check has been powered by",
    );
};

const publicHomepageShouldBeAccessible = async () => {
    await goToPublicPage();
    const remediation = await computeCurrentPageRemediation();
    await expect(remediation).toBe("bypass");
};

const adminPageShouldBeAccessible = async () => {
    await goToAdmin();
    const remediation = await computeCurrentPageRemediation("Magento Admin");
    await expect(remediation).toBe("bypass");
};

const adminPageShouldBeBanWall = async () => {
    await goToAdmin();
    const remediation = await computeCurrentPageRemediation("Magento Admin");
    await expect(remediation).toBe("ban");
};

const banIpForSeconds = async (seconds, ip) => {
    await addDecision(ip, "ban", seconds);
    await wait(1000);
};

const captchaIpForSeconds = async (seconds, ip) => {
    await addDecision(ip, "captcha", seconds);
    await wait(1000);
};

const removeAllDecisions = async () => {
    await deleteAllDecisions();
    await wait(1000);
};

const getFileContent = async (filePath) => {
    if (fs.existsSync(filePath)) {
        return fs.readFileSync(filePath, "utf8");
    }
    return "";
};

const deleteFileContent = async (filePath) => {
    if (fs.existsSync(filePath)) {
        return fs.writeFileSync(filePath, "");
    }
    return false;
};

module.exports = {
    addDecision,
    wait,
    runCron,
    goToAdmin,
    goToPublicPage,
    goToSettingsPage,
    onAdminGoToSettingsPage,
    onAdminSaveSettings,
    flushCache,
    onLoginPageLoginAsAdmin,
    publicHomepageShouldBeBanWall,
    publicHomepageShouldBeCaptchaWall,
    publicHomepageShouldBeCaptchaWallWithoutMentions,
    publicHomepageShouldBeCaptchaWallWithMentions,
    publicHomepageShouldBeAccessible,
    adminPageShouldBeAccessible,
    adminPageShouldBeBanWall,
    banIpForSeconds,
    captchaIpForSeconds,
    removeAllDecisions,
    fillInput,
    fillByName,
    selectElement,
    selectByName,
    getFileContent,
    deleteFileContent,
    setDefaultConfig,
};
