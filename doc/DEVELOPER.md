![CrowdSec Logo](images/logo_crowdsec.png)
# CrowdSec Bouncer extension for Magento 2

## Developer guide


<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**

- [Local development](#local-development)
  - [DDEV installation](#ddev-installation)
  - [Prepare DDEV Magento 2 environment](#prepare-ddev-magento-2-environment)
  - [Magento 2 installation](#magento-2-installation)
  - [Set up Magento 2](#set-up-magento-2)
  - [CrowdSec Bouncer extension installation](#crowdsec-bouncer-extension-installation)
  - [Extension quality](#extension-quality)
  - [Cron](#cron)
  - [CrowdSec CSCLI command](#crowdsec-cscli-command)
- [Commit message](#commit-message)
  - [Allowed message `type` values](#allowed-message-type-values)
- [Release process](#release-process)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->



## Local development

There are many ways to install this extension on a local Magento 2 environment.


For a quick start, you can use [DDEV-Local](https://ddev.readthedocs.io/en/stable/) and follow the below steps.

_We will suppose that you want to test on a Magento 2.4.3 instance. Change the version number if you prefer another 
release._

### DDEV installation

Please follow the [official instructions](https://ddev.readthedocs.io/en/stable/#installation). On a Linux 
distribution, this should be as simple as

    sudo apt-get install linuxbrew-wrapper
    brew tap drud/ddev && brew install ddev


### Prepare DDEV Magento 2 environment

- Create an empty folder that will contain all necessary sources (Magento 2 and this extension):
``` 
mkdir m2-sources
```
- Create an empty `.ddev` folder for DDEV and clone our pre-configured DDEV repo:
```
mkdir m2-sources/.ddev && cd m2-sources/.ddev && git clone git@github.com:julienloizelet/ddev-m2.git ./
```
- Copy some configurations file:

```      
cp .ddev/config_overrides/config.m243.yaml .ddev/config.m243.yaml
cp .ddev/additional_docker_compose/docker-compose.crowdsec.yaml .ddev/docker-compose.crowdsec.yaml
```

- Launch DDEV
```
cd .ddev && ddev start`
```
 This should take some times on the first launch as this will download all necessary docker images.


### Magento 2 installation
You will need your Magento 2 credentials to install the source code.

     ddev composer create --repository=https://repo.magento.com/ magento/project-community-edition:2.4.3


### Set up Magento 2

     ddev magento setup:install \
                           --base-url=https://m243.ddev.site \
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
                           --elasticsearch-host=elasticsearch

### CrowdSec Bouncer extension installation

     mkdir m2-sources/my-own-modules
     mkdir m2-sources/my-own-modules/crowdsec-bouncer
     cd m2-sources/my-own-modules/crowdsec-bouncer && git clone git@github.com:crowdsecurity/cs-magento-bouncer.git ./
     ddev composer config repositories.crowdsec-bouncer-module path my-own-modules/crowdsec-bouncer/
     ddev composer require crowdsec/magento2-module-bouncer:@dev
     ddev magento module:enable CrowdSec_Bouncer
     ddev magento setup:upgrade
     ddev magento cache:flush

### Extension quality

During development, you can run some static php tools to ensure quality code:  

- PHP Code Sniffer: `ddev phpcs my-own-modules/crowdsec-bouncer`
- PHP Mess Detector: `ddev phpmd my-own-modules/crowdsec-bouncer`
- PHP Stan: `ddev phpstan my-own-modules/crowdsec-bouncer`

You can also check unit tests: `ddev phpunit my-own-modules/crowdsec-bouncer/Test/Unit`

### Cron

If you want to test the CrowdSec Bouncer stream mode, you can simulate Magento 2 cron with the following command in 
a new terminal: 

     ddev cron

### CrowdSec CSCLI command

You can run every CSCLI command by prefixing it with `ddev exec -s crowdsec`. For example:


    ddev exec -s crowdsec cscli decisions add --ip 172.21.0.12 --duration 4h --type ban

    ddev exec -s crowdsec cscli bouncers add magento2-bouncer

## Commit message

In order to have an explicit commit history, we are using some commits message convention. 
See [here](https://karma-runner.github.io/6.3/dev/git-commit-msg.html) for more information.

You can use the `commit-msg` git hook that you will find in the `.githooks` folder : 

```
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

## Release process

We are using [semantic versioning](https://semver.org/) to determine a version number.

Before publishing a new release, there are some manual steps to take:

- Change the version number in the `composer.json` file
- Change the version number in the `Constants.php` file
- Update the `CHANGELOG.md` file


Then, using the [Github CLI](https://github.com/cli/cli), you can: 
- create a draft release: `gh workflow run release.yml -f tag_name=vx.y.z`
- create a prerelease:  `gh workflow run release.yml -f tag_name=vx.y.z   -f prerelease=true -f draft=false`
- publish a release: `gh workflow run release.yml -f tag_name=vx.y.z  -f draft=false`

Note that the Github action will fail if the tag `tag_name` already exits.

At the end of the Github action process, you will find a `crowdsec-magento2-module-bouncer-x.y.z.zip` file in the 
Github release assets.

 
