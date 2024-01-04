const { M2_URL } = process.env;
const ADMIN_URL = `${M2_URL}/admin/`;

const ADMIN_LOGIN = "admin";
const ADMIN_PASSWORD = "admin123";
const LAPI_URL_FROM_M2 = "https://crowdsec:8080";
const { LAPI_URL_FROM_PLAYWRIGHT } = process.env;
const { BOUNCER_KEY } = process.env;
const { DEBUG } = process.env;
const { TIMEOUT } = process.env;
const { CURRENT_IP } = process.env;
const { PROXY_IP } = process.env;
const DEBUG_LOG_PATH = `${__dirname}/../../../../../var/log/crowdsec-bouncer-debug.log`;
const JAPAN_IP = "210.249.74.42";
const FRANCE_IP = "78.119.253.85";
const { VAR_PATH, TLS_PATH } = process.env;
const AGENT_CERT_FILE = `agent.pem`;
const AGENT_KEY_FILE = `agent-key.pem`;
const CA_CERT_FILE = `ca-chain.pem`;
const BOUNCER_CERT_FILE = `bouncer.pem`;
const BOUNCER_KEY_FILE = `bouncer-key.pem`;
const WATCHER_LOGIN = "watcherLogin";
const WATCHER_PASSWORD = "watcherPassword";

module.exports = {
    ADMIN_URL,
    ADMIN_LOGIN,
    ADMIN_PASSWORD,
    DEBUG_LOG_PATH,
    M2_URL,
    BOUNCER_KEY,
    CURRENT_IP,
    DEBUG,
    FRANCE_IP,
    LAPI_URL_FROM_M2,
    JAPAN_IP,
    LAPI_URL_FROM_PLAYWRIGHT,
    PROXY_IP,
    TIMEOUT,
    AGENT_CERT_FILE,
    AGENT_KEY_FILE,
    CA_CERT_FILE,
    WATCHER_LOGIN,
    WATCHER_PASSWORD,
    BOUNCER_CERT_FILE,
    BOUNCER_KEY_FILE,
    VAR_PATH,
    TLS_PATH,
};
