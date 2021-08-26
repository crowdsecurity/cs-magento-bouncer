#!/bin/bash
# Usage : ./run-test-live-remediation.sh

YELLOW='\033[33m'
RESET='\033[0m'
if ! ddev --version >/dev/null 2>&1; then
    printf "${YELLOW}Ddev is required for this script. Please see https://ddev.readthedocs.io/en/stable/.${RESET}\n"
    exit 1
fi

HOSTNAME=$(ddev exec printenv DDEV_HOSTNAME | sed 's/\r//g')

cd "../../"

npx cross-env  \
DEBUG=1 \
M2_URL=https://${HOSTNAME} \
CURRENT_IP=$(ddev find-ip host)  \
LAPI_URL_FROM_PLAYWRIGHT=http://$HOSTNAME:8080 \
yarn --cwd ./ test  \
    --detectOpenHandles \
    --runInBand \
    --json \
    --outputFile=.test-results-m243.json \
    "./__tests__/2-live-mode-remediation.js"
