#!/bin/bash
# Run test suite
# Usage: ./run-tests.sh  <type>  <file-list>
# type : host, docker or ci (default: host)
# file-list : a list of test files (default: empty so it will run all the tests)
# Example: ./run-tests.sh docker "./__tests__/1-config.js ./__tests__/2-live-mode-remediation.js"

YELLOW='\033[33m'
RESET='\033[0m'
if ! ddev --version >/dev/null 2>&1; then
    printf "${YELLOW}Ddev is required for this script. Please see doc/DEVELOPER.md.${RESET}\n"
    exit 1
fi


TYPE=${1:-host}
FILE_LIST=${2:-""}


case $TYPE in
  "host")
    echo "Running with host stack"
    ;;

  "docker")
    echo "Running with ddev docker stack"
    ;;


  "ci")
    echo "Running in CI context"
    ;;

  *)
    echo "Unknown param '${TYPE}'"
    echo "Usage: ./run-tests.sh  <type>  <file-list>"
    exit 1
    ;;
esac


HOSTNAME=$(ddev exec printenv DDEV_HOSTNAME | sed 's/\r//g')
M2VERSION=$(ddev exec printenv DDEV_PROJECT | sed 's/\r//g')
M2_URL=https://$HOSTNAME
PROXY_IP=$(ddev find-ip ddev-router)
BOUNCER_KEY=$(ddev exec bin/magento config:show crowdsec_bouncer/general/connection/api_key | sed 's/\r//g')
JEST_PARAMS="--detectOpenHandles --runInBand"
# If FAIL_FAST, will exit on first individual test fail
# @see CustomEnvironment.js
FAIL_FAST=true


case $TYPE in
  "host")
    cd "../"
    DEBUG_STRING="PWDEBUG=1"
    YARN_PATH="./"
    COMMAND="yarn --cwd ${YARN_PATH} cross-env"
    LAPI_URL_FROM_PLAYWRIGHT=http://$HOSTNAME:8080
    CURRENT_IP=$(ddev find-ip host)
    TIMEOUT=31000
    HEADLESS=false
    ;;

  "docker")
    DEBUG_STRING=""
    YARN_PATH="./var/www/html/my-own-modules/crowdsec-bouncer/Test/EndToEnd"
    COMMAND="ddev exec -s playwright yarn --cwd ${YARN_PATH} cross-env"
    LAPI_URL_FROM_PLAYWRIGHT=http://crowdsec:8080
    CURRENT_IP=$(ddev find-ip playwright)
    TIMEOUT=31000
    HEADLESS=true
    ;;

  "ci")
    DEBUG_STRING="DEBUG=pw:api"
    YARN_PATH="./var/www/html/my-own-modules/crowdsec-bouncer/Test/EndToEnd"
    COMMAND="ddev exec -s playwright xvfb-run --auto-servernum -- yarn --cwd ${YARN_PATH} cross-env"
    LAPI_URL_FROM_PLAYWRIGHT=http://crowdsec:8080
    CURRENT_IP=$(ddev find-ip playwright)
    TIMEOUT=31000
    HEADLESS=true
    ;;

  *)
    echo "Unknown param '${TYPE}'"
    echo "Usage: ./run-tests.sh  <type>  <file-list>"
    exit 1
    ;;
esac



# Run command

$COMMAND \
M2_URL=$M2_URL \
$DEBUG_STRING \
BOUNCER_KEY=$BOUNCER_KEY \
PROXY_IP=$PROXY_IP  \
LAPI_URL_FROM_PLAYWRIGHT=$LAPI_URL_FROM_PLAYWRIGHT \
CURRENT_IP=$CURRENT_IP \
TIMEOUT=$TIMEOUT \
HEADLESS=$HEADLESS \
FAIL_FAST=$FAIL_FAST \
yarn --cwd $YARN_PATH test \
    $JEST_PARAMS \
    --json \
    --outputFile=./.test-results-$M2VERSION.json \
    $FILE_LIST
