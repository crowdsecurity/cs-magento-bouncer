const {
    notify,
    goToAdmin,
    onAdminGoToSettingsPage,
    onLoginPageLoginAsAdmin,
    storeCookies,
} = require("../utils/helpers");

describe(`Setup CrowdSec extension`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    it('Should login to M2 admin"', async () => {
        // "Login" page
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await storeCookies();
    });

    it('Should go on CrowdSec Bouncer section"', async () => {
        // "CrowdSec Bouncer" page
        await onAdminGoToSettingsPage();
    });

    // TODO: set config values, click on test button and save
});
