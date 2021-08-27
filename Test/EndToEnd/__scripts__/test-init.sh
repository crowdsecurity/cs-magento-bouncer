#!/bin/bash
# Prepare CrowdSec and Playwright container before testing
# Usage : ./test-init.sh

YELLOW='\033[33m'
RESET='\033[0m'
if ! ddev --version >/dev/null 2>&1; then
    printf "${YELLOW}Ddev is required for this script. Please see https://ddev.readthedocs.io/en/stable/.${RESET}\n"
    exit 1
fi

ddev exec -s crowdsec cscli machines add watcherLogin  --password watcherPassword
ddev exec -s playwright yarn --cwd ./var/www/html/my-own-modules/crowdsec-bouncer/Test/EndToEnd --force && \
ddev exec -s playwright npx --yes cross-env
