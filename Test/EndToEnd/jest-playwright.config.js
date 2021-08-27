const debug = process.env.DEBUG;
module.exports = {
    launchOptions: {
        headless: !debug,
    },
    connectOptions: debug ? { slowMo: 100 } : { slowMo: 50 },
    exitOnPageError: false,
    contextOptions: {
        ignoreHTTPSErrors: true,
        viewport: {
            width: 1920,
            height: 1080,
        },
    },
    browsers: ["chromium"],
    devices: [],
};
