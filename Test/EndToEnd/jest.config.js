module.exports = {
    preset: "jest-playwright-preset",
    testRunner: "jest-circus/runner",
    testEnvironment: "./CustomEnvironment.js",
    testSequencer: "./testSequencer.js",
    setupFilesAfterEnv: ["expect-playwright"],
    transformIgnorePatterns: ["/node_modules/(?!(axios|axios-ntlm|soap))"],
};
