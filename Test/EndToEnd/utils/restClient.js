const axios = require("axios").default;
const https = require("https");

const { M2_URL, ADMIN_LOGIN, ADMIN_PASSWORD } = require("./constants");

const httpClient = axios.create({
    baseURL: M2_URL,
    timeout: 5000,
    httpsAgent: new https.Agent({
        rejectUnauthorized: false,
    }),
    validateStatus: (status) => {
        return status >= 200 && status < 500; // Resolve even if 403 or 401
    },
});

let authenticated = false;

const auth = async () => {
    if (authenticated) {
        return;
    }
    try {
        const response = await httpClient.post(
            "/index.php/rest/V1/integration/admin/token",
            {
                username: ADMIN_LOGIN,
                password: ADMIN_PASSWORD,
            },
        );

        httpClient.defaults.headers.common.Authorization = `Bearer ${response.data}`;
        authenticated = true;
    } catch (error) {
        console.debug(
            "ADMIN_LOGIN, ADMIN_PASSWORD",
            ADMIN_LOGIN,
            ADMIN_PASSWORD,
        );
        console.error(error);
    }
};

module.exports.testCustomerRest = async () => {
    await auth();
    const response = await httpClient.get("/index.php/rest/V1/customers/1");
    return response;
};
