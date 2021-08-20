const BASE_URL = process.env.M2_URL;
const ADMIN_URL = `${BASE_URL}/admin/`;

const { MAGENTO2_VERSION } = process.env;
const ADMIN_LOGIN = "admin";
const ADMIN_PASSWORD = "admin123";
const LAPI_URL_FROM_M2 = "http://crowdsec:8080";
const { LAPI_URL_FROM_PLAYWRIGHT } = process.env;
const WATCHER_LOGIN = "watcherLogin";
const WATCHER_PASSWORD = "watcherPassword";
const DEBUG = !!process.env.DEBUG;
const TIMEOUT = (process.env.DEBUG ? 5 * 60 : 15) * 1000;
const OTHER_IP = "1.2.3.4";
const M243 = MAGENTO2_VERSION === "2.4.3";
const M230 = MAGENTO2_VERSION === "2.3.0";
const { CURRENT_IP } = process.env;
const { PROXY_IP } = process.env;

module.exports = {
    ADMIN_URL,
    BASE_URL,
    ADMIN_LOGIN,
    ADMIN_PASSWORD,
    LAPI_URL_FROM_M2,
    LAPI_URL_FROM_PLAYWRIGHT,
    OTHER_IP,
    M230,
    M243,
    DEBUG,
    TIMEOUT,
    MAGENTO2_VERSION,
    CURRENT_IP,
    PROXY_IP,
    WATCHER_LOGIN,
    WATCHER_PASSWORD,
};
