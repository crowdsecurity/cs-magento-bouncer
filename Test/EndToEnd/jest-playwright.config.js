const headless = process.env.HEADLESS;
module.exports = {
    launchOptions: {
        headless,
    },
    connectOptions: { slowMo: 150 },
    exitOnPageError: false,
    contextOptions: {
        ignoreHTTPSErrors: true,
    },
    browsers: ["chromium"],
    devices: ["Desktop Chrome"],
};
