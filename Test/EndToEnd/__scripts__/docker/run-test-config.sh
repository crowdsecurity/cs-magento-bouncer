#!/bin/bash
# Usage : ./run-test-config.sh https://m243.ddev.site

YELLOW='\033[33m'
RESET='\033[0m'
if ! ddev --version >/dev/null 2>&1; then
    printf "${YELLOW}Ddev is required for this script. Please see https://ddev.readthedocs.io/en/stable/.${RESET}\n"
    exit 1
fi

M2_URL=$1


ddev exec -s playwright npx cross-env M2_URL=$M2_URL BOUNCER_KEY=$(ddev get-bouncer-key magento2) PROXY_IP=$(ddev find-ip ddev-router)  yarn --cwd ./var/www/html/my-own-modules/crowdsec-bouncer/Test/EndToEnd test \
    --detectOpenHandles \
    --runInBand \
    --json \
    --outputFile=./.test-results-m243.json \
    "./__tests__/1-config.js"
