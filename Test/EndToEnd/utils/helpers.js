/* eslint-disable no-undef */
const fs = require("fs");
const { addDecision, deleteAllDecisions } = require("./watcherClient");
const {
    ADMIN_URL,
    M2_URL,
    ADMIN_LOGIN,
    ADMIN_PASSWORD,
    DEBUG,
    TIMEOUT,
} = require("./constants");

const COOKIES_FILE_PATH = `${__dirname}/../.cookies.json`;

const notify = (message) => {
    if (DEBUG) {
        console.debug(message);
    }
};

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

jest.setTimeout(TIMEOUT);

const fillInput = async (optionId, value) => {
    await page.fill(`[id=${optionId}]`, `${value}`);
};

const selectElement = async (selectId, valueToSelect) => {
    await page.selectOption(`[id=${selectId}]`, `${valueToSelect}`);
};

const goToAdmin = async () => {
    await page.goto(ADMIN_URL, { waitUntil: "networkidle" });
};

const goToPublicPage = async () => {
    await page.goto(`${M2_URL}`);
};

const onAdminGoToSettingsPage = async () => {
    // CrowdSec Menu

    await page.click("#menu-magento-backend-stores > a");
    await page.waitForLoadState("networkidle");

    await page.click(
        '#menu-magento-backend-stores .item-system-config:has-text("Configuration") ',
    );
    await page.waitForLoadState("networkidle");

    await page.click(
        '#system_config_tabs .config-nav-block:has-text("Security")',
    );
    await page.waitForLoadState("networkidle");
    await page.click('.config-nav-block li:has-text("CrowdSec Bouncer")');
    await page.waitForLoadState("networkidle");
    await expect(page).toMatchText(
        "#crowdsec_bouncer_general-head",
        "General settings",
    );
};

const goToSettingsPage = async () => {
    await goToAdmin();
    await onAdminGoToSettingsPage();
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

const remediationShouldUpdate = async (
    accessibleTextInTitle,
    initialRemediation,
    newRemediation,
    timeoutMs,
    intervalMs = 1000,
) =>
    new Promise((resolve, reject) => {
        let checkRemediationTimeout;
        let checkRemediationInterval;
        let initialPassed = false;
        const stopTimers = () => {
            if (checkRemediationInterval) {
                clearInterval(checkRemediationInterval);
            }
            if (checkRemediationTimeout) {
                clearTimeout(checkRemediationTimeout);
            }
        };

        checkRemediationInterval = setInterval(async () => {
            await page.reload({ waitUntil: "networkidle" });
            const remediation = await computeCurrentPageRemediation(
                accessibleTextInTitle,
            );
            if (remediation === newRemediation) {
                stopTimers();
                if (initialPassed) {
                    resolve();
                } else {
                    reject(
                        new Error({
                            errorType: "INITIAL_REMEDIATION_NEVER_HAPPENED",
                            type: remediation,
                        }),
                    );
                }
            } else if (remediation === initialRemediation) {
                initialPassed = true;
            } else {
                stopTimers();
                reject(
                    new Error({
                        errorType: "WRONG_REMEDIATION_HAPPENED",
                        type: remediation,
                    }),
                );
            }
        }, intervalMs);
        checkRemediationTimeout = setTimeout(() => {
            stopTimers();
            reject(new Error({ errorType: "NEW_REMEDIATION_NEVER_HAPPENED" }));
        }, timeoutMs);
    });

const storeCookies = async () => {
    const cookies = await context.cookies();
    const cookieJson = JSON.stringify(cookies);
    fs.writeFileSync(COOKIES_FILE_PATH, cookieJson);
};

const loadCookies = async (context) => {
    const cookies = fs.readFileSync(COOKIES_FILE_PATH, "utf8");
    const deserializedCookies = JSON.parse(cookies);
    await context.addCookies(deserializedCookies);
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
    notify,
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
    remediationShouldUpdate,
    selectElement,
    storeCookies,
    loadCookies,
    getFileContent,
    deleteFileContent,
};
