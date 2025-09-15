![CrowdSec Logo](images/logo_crowdsec.png)

# CrowdSec Bouncer extension for Magento 2

## Developer guide

**Table of Contents**

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->

- [Local development](#local-development)
  - [DDEV setup](#ddev-setup)
    - [DDEV installation](#ddev-installation)
    - [Create a Magento 2 DDEV project with some DDEV add-ons](#create-a-magento-2-ddev-project-with-some-ddev-add-ons)
    - [Magento 2 installation](#magento-2-installation)
    - [Configure Magento 2 for local development](#configure-magento-2-for-local-development)
    - [Crowdsec Bouncer extension installation](#crowdsec-bouncer-extension-installation)
  - [Extension quality](#extension-quality)
  - [End-to-end tests](#end-to-end-tests)
  - [Cron](#cron)
  - [CrowdSec CSCLI command](#crowdsec-cscli-command)
  - [Varnish](#varnish)
    - [Varnish debug](#varnish-debug)
  - [Auto Prepend File mode](#auto-prepend-file-mode)
- [Commit message](#commit-message)
  - [Allowed message `type` values](#allowed-message-type-values)
- [Update documentation table of contents](#update-documentation-table-of-contents)
- [Release process](#release-process)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

## Local development

There are many ways to install this extension on a local Magento 2 environment.

We are using [DDEV](https://docs.ddev.com/en/stable/) because it is quite simple to use and customize.

You may use your own local stack, but we provide here some useful tools that depends on DDEV.

### DDEV setup

For a quick start, follow the below steps.

The final structure of the project will look like below.

```
m2-sources (choose the name you want for this folder)
│
│ (Magento 2 sources installed with composer)
│
└───.ddev
│   │
│   │ (DDEV files)
│
└───my-own-modules (do not change this folder name)
    │
    │
    └───crowdsec-bouncer (do not change this folder name)
       │
       │ (Cloned sources of this repo)

```

**N.B.:** you can use whatever name you like for the folder `m2-sources` but, in order to use our pre-configured ddev
commands, you must respect the sub folders naming: `my-own-modules` and `crowdsec-bouncer`.

#### DDEV installation

This project is fully compatible with DDEV 1.21.6, and it is recommended to use this specific version. For the DDEV
installation, please follow the [official instructions](https://docs.ddev.com/en/stable/).

#### Create a Magento 2 DDEV project with some DDEV add-ons

```bash
mkdir m2-sources && cd m2-sources
ddev config --project-type=magento2 --project-name=your-project-name --php-version=8.1 --docroot=pub --create-docroot --disable-settings-management
ddev get ddev/ddev-redis
ddev get ddev/ddev-memcached
ddev get ddev/ddev-elasticsearch
ddev get julienloizelet/ddev-tools
ddev get julienloizelet/ddev-playwright
ddev start
```

#### Magento 2 installation

You will need your Magento 2 credentials to install the source code.

```bash
 ddev composer create --repository=https://repo.magento.com/ magento/project-community-edition -y
```

##### Set up Magento 2

```bash
 ddev magento setup:install \
                       --base-url=https://your-project-name.ddev.site/ \
                       --db-host=db \
                       --db-name=db \
                       --db-user=db \
                       --db-password=db \
                       --backend-frontname=admin \
                       --admin-firstname=admin \
                       --admin-lastname=admin \
                       --admin-email=admin@admin.com \
                       --admin-user=admin \
                       --admin-password=admin123 \
                       --language=en_US \
                       --currency=USD \
                       --timezone=America/Chicago \
                       --use-rewrites=1 \
                       --elasticsearch-host=elasticsearch --search-engine=elasticsearch7
```

#### Configure Magento 2 for local development

```bash

ddev magento config:set admin/security/password_is_forced 0
ddev magento config:set admin/security/password_lifetime 0
ddev magento module:disable Magento_AdminAdobeImsTwoFactorAuth (Magento >= 2.4.6 only)
ddev magento module:disable Magento_TwoFactorAuth
ddev magento setup:performance:generate-fixtures setup/performance-toolkit/profiles/ce/small.xml
ddev magento c:c
```

#### Crowdsec Bouncer extension installation

```bash
 cd m2-sources
 mkdir -p my-own-modules/crowdsec-bouncer
 cd my-own-modules/crowdsec-bouncer
 git clone git@github.com:crowdsecurity/cs-magento-bouncer.git ./
 ddev composer config repositories.crowdsec-bouncer-module path my-own-modules/crowdsec-bouncer/
 ddev composer require crowdsec/magento2-module-bouncer:@dev
 ddev magento module:enable CrowdSec_Bouncer
 ddev magento setup:upgrade
 ddev magento cache:flush
 ddev get julienloizelet/ddev-crowdsec-php
 ddev restart
```

### Extension quality

During development, you can run some static php tools to ensure quality code:

- PHP Code Sniffer: `ddev phpcs my-own-modules/crowdsec-bouncer --ignore="*/node_modules/*"`
- PHP Mess Detector: `ddev phpmd --exclude "node_modules"  my-own-modules/crowdsec-bouncer`
- PHP Stan: `ddev phpstan my-own-modules/crowdsec-bouncer`

You can also check unit tests: `ddev phpunit my-own-modules/crowdsec-bouncer/Test/Unit`

### End-to-end tests

We are using a Jest/Playwright Node.js stack to launch a suite of end-to-end tests.

**Please note** that those tests modify local configurations and log content on the fly.

Tests code is in the `Test/EndToEnd` folder. You should have to `chmod +x` the scripts you will find in `Test/EndToEnd/__scripts__`.

As we use a TLS ready CrowdSec container, you have first to copy some certificates and key:

```bash
cd m2-sources
mkdir -p var/crowdsec/tls
cp -r .ddev/okaeli-add-on/custom_files/crowdsec/cfssl/* var/crowdsec/tls
```

To run a specific tasks from browser, we created a `launchCron.php` and `cacheActions.php` scripts that you have to copy before testing some features (stream mode for example):

```bash
cp .ddev/okaeli-add-on/magento2/custom_scripts/cronLaunch.php m2-sources/pub/cronLaunch.php
cp .ddev/okaeli-add-on/magento2/custom_scripts/crowdsec/cacheActions.php m2-sources/pub/cacheActions.php
chmod +x m2-sources/pub/cronLaunch.php m2-sources/pub/cacheActions.php
```

Then you can use the `run-test.sh` script to run the tests:

- the first parameter specifies if you want to run the test on your machine (`host`) or in the
  docker containers (`docker`). You can also use `ci` if you want to have the same behavior as in GitHub action.
- the second parameter list the test files you want to execute. If empty, all the test suite will be launched.

For example:

```bash
./run-tests.sh host "./__tests__/1-config.js"
./run-tests.sh docker "./__tests__/1-config.js"
./run-tests.sh host
./run-tests.sh host "./__tests__/1-config.js  ./__tests__/3-stream-mode.js"
```

Before testing with the `docker` or `ci` parameter, you have to install all the required dependencies
in the playwright container with this command :

```bash
./test-init.sh
```

If you want to test with the `host` parameter, you will have to install manually all the required dependencies:

```bash
yarn --cwd ./Test/EndToEnd --force
yarn global add cross-env
```

### Cron

If you want to test the CrowdSec Bouncer stream mode, you can simulate Magento 2 cron with the following command in
a new terminal:

```bash
 ddev cron
```

You should find a `var/log/magento.cron.log` for debug.

### CrowdSec CSCLI command

You can run every CSCLI command by prefixing it with `ddev exec -s crowdsec`. For example:

```bash
ddev exec -s crowdsec cscli decisions add --ip 172.21.0.12 --duration 4h --type ban

ddev exec -s crowdsec cscli bouncers add magento2-bouncer
```

### Varnish

If you want to test with Varnish, please follow these instructions:

First, you should configure your Magento 2 instance to use Varnish as full page cache:

```bash
ddev magento config:set system/full_page_cache/caching_application 2
```

You can also add the varnish IP as trusted proxy IP:

```bash
ddev magento config:set crowdsec_bouncer/advanced/remediation/trust_ip_forward_list $(ddev find-ip varnish)
```

Then, you can add specific files for Varnish and restart:

```bash
ddev get ddev/ddev-varnish
cp .ddev/okaeli-add-on/magento2/custom_files/default.vcl .ddev/varnish/default.vcl
ddev restart
```

Finally, we need to change the ACL part for purge process:

```bash
ddev replace-acl $(ddev find-ip ddev-router)
ddev reload-vcl
```

For information, here are the differences between the back office generated `default.vcl` and the `default.vcl` we use:

- We changed the probe url from `"/pub/health_check.php"` to `"/health_check.php"` as explained in the official documentation:

```
 .probe = {
    .url = "/health_check.php";
    .timeout = 2s;
    .interval = 5s;
    .window = 10;
    .threshold = 5;
    }
```

- We added this part for Marketplace EQP Varnish test simulation as explained in the official documentation:

```
if (resp.http.x-varnish ~ " ") {
           set resp.http.X-EQP-Cache = "HIT";
       } else {
           set resp.http.X-EQP-Cache = "MISS";
}
```

#### Varnish debug

To see if purge works, you can do :

```bash
ddev exec -s varnish varnishlog -g request -q \'ReqMethod eq "PURGE"\'
```

And then, from another terminal, flush the cache :

```bash
ddev magento cache:flush
```

You should see in the log the following content:

```
VCL_call       RECV
VCL_acl        MATCH purge "your-ddev-router-ip"
VCL_return     synth
VCL_call       HASH
VCL_return     lookup
RespProtocol   HTTP/1.1
RespStatus     200
RespReason     Purged
```

### Auto Prepend File mode

First, you have to copy the `crowdsec-prepend.php` file to your `app/etc` folder:

```bash
cp m2-sources/my-own-modules/crowdsec-bouncer/crowdsec-prepend.php m2-sources/app/etc/crowdsec-prepend.php
```

Then, to enable the `auto prepend file` mode, you can run the following command that will modify and reload nginx
configuration:

```bash
ddev crowdsec-prepend-nginx
```

To disable the `auto prepend file` mode, please restart:

```bash
ddev restart
```

## Commit message

In order to have an explicit commit history, we are using some commits message convention with the following format:

    <type>(<scope>): <subject>

Allowed `type` are defined below.
`scope` value intends to clarify which part of the code has been modified. It can be empty or `*` if the change is a
global or difficult to assign to a specific part.
`subject` describes what has been done using the imperative, present tense.

Example:

    feat(admin): Add css for admin actions

You can use the `commit-msg` git hook that you will find in the `.githooks` folder :

```bash
cp .githooks/commit-msg .git/hooks/commit-msg
chmod +x .git/hooks/commit-msg
```

### Allowed message `type` values

- chore (automatic tasks; no production code change)
- ci (updating continuous integration process; no production code change)
- comment (commenting;no production code change)
- docs (changes to the documentation)
- feat (new feature for the user)
- fix (bug fix for the user)
- refactor (refactoring production code)
- style (formatting; no production code change)
- test (adding missing tests, refactoring tests; no production code change)

## Update documentation table of contents

To update the table of contents in the documentation, you can use [the `doctoc` tool](https://github.com/thlorenz/doctoc).

First, install it:

```bash
npm install -g doctoc
```

Then, run it in the root folder:

```bash
doctoc doc/* --maxlevel 4
```

## Release process

We are using [semantic versioning](https://semver.org/) to determine a version number.

Before publishing a new release, there are some manual steps to take:

- Change the version number in the `composer.json` file
- Change the version number in the `Constants.php` file
- Update the `CHANGELOG.md` file

Then, using the [GitHub CLI](https://github.com/cli/cli), you can:

- create a draft release: `gh workflow run release.yml -f tag_name=vx.y.z -f draft=true`
- publish a prerelease: `gh workflow run release.yml -f tag_name=vx.y.z -f prerelease=true`
- publish a release: `gh workflow run release.yml -f tag_name=vx.y.z`

Note that the GitHub action will fail if the tag `tag_name` already exits.

At the end of the GitHub action process, you will find a `crowdsec-magento2-module-bouncer-x.y.z.zip` file in the
GitHub release assets.
