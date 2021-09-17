/* eslint-disable no-param-reassign */
const soap = require("soap");
const axios = require("axios").default;

const https = require("https");
const { M2_URL, ADMIN_LOGIN, ADMIN_PASSWORD } = require("./constants");

const axiosInstance = axios.create({
    httpsAgent: new https.Agent({
        rejectUnauthorized: false,
    }),
});

const getToken = async () => {
    let token = null;
    try {
        const url = `${M2_URL}/index.php/soap/?wsdl&services=integrationAdminTokenServiceV1`;
        const requestArgs = {
            username: ADMIN_LOGIN,
            password: ADMIN_PASSWORD,
        };

        await soap
            .createClientAsync(url, {
                request: axiosInstance.request,
            })
            .then(async (client) => {
                const result =
                    await client.integrationAdminTokenServiceV1CreateAdminAccessTokenAsync(
                        requestArgs,
                    );
                token = result[0].result;
            });
    } catch (error) {
        console.error("Failed to retrieve token");
    }

    return token;
};

const makeTestCall = async (token) => {
    let result = null;
    try {
        const url = `${M2_URL}/soap/default?wsdl&services=directoryCurrencyInformationAcquirerV1`;

        await soap
            .createClientAsync(url, {
                request: axiosInstance.request,
            })
            .then(async (client) => {
                client.setSecurity(new soap.BearerSecurity(token));

                result = await client.describe();
            })
            .catch((error) => console.error(error));
    } catch (error) {
        console.error(error);
    }
    return result;
};

module.exports.testSoap = async () => {
    const token = await getToken();
    if (token) {
        return makeTestCall(token);
    }
    return "ERROR";
};
