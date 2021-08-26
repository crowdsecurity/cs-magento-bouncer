#!/bin/bash
# Usage : ./run-test-config.sh

YELLOW='\033[33m'
RESET='\033[0m'
if ! ddev --version >/dev/null 2>&1; then
    printf "${YELLOW}Ddev is required for this script. Please see https://ddev.readthedocs.io/en/stable/.${RESET}\n"
    exit 1
fi

HOSTNAME=$(ddev exec printenv DDEV_HOSTNAME | sed 's/\r//g')


cd "../../"


npx cross-env \
M2_URL=https://$HOSTNAME \
DEBUG=1 \
BOUNCER_KEY=$(ddev get-bouncer-key magento2) \
PROXY_IP=$(ddev find-ip ddev-router)  \
yarn --cwd ./ test \
    --detectOpenHandles \
    --runInBand \
    --json \
    --outputFile=./.test-results-m243.json \
    "./__tests__/1-config.js"
