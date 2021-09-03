const { M2_URL } = process.env;
const ADMIN_URL = `${M2_URL}/admin/`;

const ADMIN_LOGIN = "admin";
const ADMIN_PASSWORD = "admin123";
const LAPI_URL_FROM_M2 = "http://crowdsec:8080";
const { LAPI_URL_FROM_PLAYWRIGHT } = process.env;
const { BOUNCER_KEY } = process.env;
const WATCHER_LOGIN = "watcherLogin";
const WATCHER_PASSWORD = "watcherPassword";
const { DEBUG } = process.env;
const { TIMEOUT } = process.env;
const { CURRENT_IP } = process.env;
const { PROXY_IP } = process.env;
const DEBUG_LOG_PATH = `${__dirname}/../../../../../var/log/crowdsec-bouncer-debug.log`;

module.exports = {
    ADMIN_URL,
    ADMIN_LOGIN,
    ADMIN_PASSWORD,
    DEBUG_LOG_PATH,
    M2_URL,
    BOUNCER_KEY,
    CURRENT_IP,
    DEBUG,
    LAPI_URL_FROM_M2,
    LAPI_URL_FROM_PLAYWRIGHT,
    PROXY_IP,
    TIMEOUT,
    WATCHER_LOGIN,
    WATCHER_PASSWORD,
};
