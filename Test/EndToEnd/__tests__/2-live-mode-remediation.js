const { CURRENT_IP } = require("../utils/constants");

const {
    notify,
    publicHomepageShouldBeBanWall,
    publicHomepageShouldBeCaptchaWallWithMentions,
    publicHomepageShouldBeAccessible,
    banIpForSeconds,
    captchaIpForSeconds,
    loadCookies,
    removeAllDecisions,
} = require("../utils/helpers");

describe(`Run in Live mode`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    beforeAll(async () => {
        await loadCookies(context);
        await removeAllDecisions();
    });

    it('Should display the homepage with no remediation"', async () => {
        await publicHomepageShouldBeAccessible();
    });

    it('Should display a captcha wall"', async () => {
        await captchaIpForSeconds(15 * 60, CURRENT_IP);
        await publicHomepageShouldBeCaptchaWallWithMentions();
    });

    it('Should display a ban wall"', async () => {
        await banIpForSeconds(15 * 60, CURRENT_IP);
        await publicHomepageShouldBeBanWall();
    });
});
