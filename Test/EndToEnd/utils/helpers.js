const notifier = require("node-notifier");
const path = require("path");
const fs = require("fs");
const { addDecision, deleteAllDecisions } = require("./watcherClient");
const {
    ADMIN_URL,
    BASE_URL,
    ADMIN_LOGIN,
    ADMIN_PASSWORD,
    DEBUG,
    TIMEOUT,
} = require("./constants");

const COOKIES_FILE_PATH = `${__dirname}/../.cookies.json`;

const notify = (message) => {
    if (DEBUG) {
        console.debug(message);
        notifier.notify({
            title: "CrowdSec automation",
            message,
            icon: path.join(__dirname, "./icon.png"),
        });
    }
};

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

jest.setTimeout(TIMEOUT);

const waitForNavigation = page.waitForNavigation();

const goToAdmin = async () => {
    await page.goto(ADMIN_URL);
    await waitForNavigation;
};

const goToPublicPage = async () => {
    await page.goto(`${BASE_URL}`);
    await waitForNavigation;
};

const onAdminGoToSettingsPage = async () => {
    // CrowdSec Menu
    await page.click("#menu-magento-backend-stores > a");
    await waitForNavigation;
    await page.click(
        '#menu-magento-backend-stores .item-system-config:has-text("Configuration") ',
    );
    await waitForNavigation;
    await page.click(
        '#system_config_tabs .config-nav-block:has-text("Security")',
    );
    await waitForNavigation;
    await page.click('.config-nav-block li:has-text("CrowdSec Bouncer")');
    await waitForNavigation;
    await expect(page).toHaveText(
        "#crowdsec_bouncer_general-head",
        "General settings",
    );
};

const onLoginPageLoginAsAdmin = async () => {
    await page.fill("#username", ADMIN_LOGIN);
    await page.fill("#login", ADMIN_PASSWORD);
    await page.waitForSelector(".action-login");
    await page.click(".action-login");
    await waitForNavigation;
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

const fillInput = async (optionName, value) => {
    await page.fill(`[name=${optionName}]`, `${value}`);
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
            await page.reload();
            await waitForNavigation;
            const remediation = await computeCurrentPageRemediation(
                accessibleTextInTitle,
            );
            if (remediation === newRemediation) {
                stopTimers();
                if (initialPassed) {
                    resolve();
                } else {
                    reject({
                        errorType: "INITIAL_REMEDIATION_NEVER_HAPPENED",
                        type: remediation,
                    });
                }
            } else if (remediation === initialRemediation) {
                initialPassed = true;
            } else {
                stopTimers();
                reject({
                    errorType: "WRONG_REMEDIATION_HAPPENED",
                    type: remediation,
                });
            }
        }, intervalMs);
        checkRemediationTimeout = setTimeout(() => {
            stopTimers();
            reject({ errorType: "NEW_REMEDIATION_NEVER_HAPPENED" });
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

module.exports = {
    notify,
    addDecision,
    wait,
    waitForNavigation,
    goToAdmin,
    goToPublicPage,
    onAdminGoToSettingsPage,
    onLoginPageLoginAsAdmin,
    publicHomepageShouldBeBanWall,
    publicHomepageShouldBeCaptchaWall,
    publicHomepageShouldBeCaptchaWallWithoutMentions,
    publicHomepageShouldBeCaptchaWallWithMentions,
    publicHomepageShouldBeAccessible,
    banIpForSeconds,
    captchaIpForSeconds,
    removeAllDecisions,
    fillInput,
    remediationShouldUpdate,
    storeCookies,
    loadCookies,
};
