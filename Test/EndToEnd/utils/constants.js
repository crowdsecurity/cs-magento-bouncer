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
const EVENT_LOG_PATH = `${__dirname}/../../../../../var/log/crowdsec-events.log`;
const JAPAN_IP = "210.249.74.42";
const FRANCE_IP = "78.119.253.85";
const { AGENT_TLS_PATH } = process.env;
const AGENT_CERT_PATH = `${AGENT_TLS_PATH}/agent.pem`;
const AGENT_KEY_PATH = `${AGENT_TLS_PATH}/agent-key.pem`;
const CA_CERT_PATH = `${AGENT_TLS_PATH}/ca-chain.pem`;
const BOUNCER_CERT_PATH = "crowdsec/tls/councer.pem";
const BOUNCER_KEY_PATH = "crowdsec/tls/bouncer-key.pem";
const BOUNCER_CA_CERT_PATH = "crowdsec/tls/ca-chain.pem";
const WATCHER_LOGIN = "watcherLogin";
const WATCHER_PASSWORD = "watcherPassword";

module.exports = {
    ADMIN_URL,
    ADMIN_LOGIN,
    ADMIN_PASSWORD,
    DEBUG_LOG_PATH,
    EVENT_LOG_PATH,
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
    AGENT_CERT_PATH,
    AGENT_KEY_PATH,
    CA_CERT_PATH,
    WATCHER_LOGIN,
    WATCHER_PASSWORD,
    BOUNCER_CERT_PATH,
    BOUNCER_KEY_PATH,
    BOUNCER_CA_CERT_PATH,
};
