![CrowdSec Logo](images/logo_crowdsec.png)

# CrowdSec Bouncer extension for Magento 2

## Installation Guide


<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**

- [Requirements](#requirements)
- [Installation](#installation)
- [Post Installation](#post-installation)
  - [Enable the module](#enable-the-module)
  - [System Upgrade](#system-upgrade)
  - [Clear Cache](#clear-cache)
  - [Deploy static content](#deploy-static-content)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->


## Requirements

- Magento >= 2.3

## Installation

Use `Composer` by simply adding `crowdsec/magento2-module-bouncer` as a dependency:

    composer require crowdsec/magento2-module-bouncer 

## Post Installation

### Enable the module

After the installment of the module source code, the module has to be enabled by the Magento 2 CLI.

    bin/magento module:enable CrowdSec_Bouncer

### System Upgrade

After enabling the module, the Magento 2 system must be upgraded.

If the system mode is set to production, run the compile command first. This is not necessary for the developer mode.


    bin/magento setup:di:compile

Then run the upgrade command:


    bin/magento setup:upgrade

    
### Clear Cache

The Magento 2 cache should be cleared by running the flush command.

    bin/magento cache:flush

### Deploy static content

At last, you have to deploy the static content:

    bin/magento setup:static-content:deploy -f


## Troubleshooting

### Higher matching error

If a new version `y.y.y` has been published in Packagist and the Marketplace review process of this version is still in progress,
you could encounter the following error:

> Higher matching version y.y.y of crowdsec/magento2-module-bouncer was found in public repository packagist.org
> than x.x.x in private https://repo.magento.com. Public package might've been taken over by a malicious entity,
> please investigate and update package requirement to match the version from the private repository

This error is due to the `magento/composer-dependency-version-audit-plugin` composer plugin introduced in Magento `2.4.3` as a security measure [against Dependency Confusion attacks](https://support.magento.com/hc/en-us/articles/4410675867917-Composer-plugin-against-Dependency-Confusion-attacks).

#### Install the latest Marketplace release

To avoid this error and install the latest known Marketplace release `x.x.x`, you could run:

```bash
composer require crowdsec/magento2-module-bouncer --no-plugins
```

#### Install the latest Packagist release

To avoid this error and install the latest known Packagist release `y.y.y`, you could modify the root `composer.
json` of your Magento project by setting the `repo.magento.com` repository as non-canonical:

```
"repositories": {
    "0": {
        "type": "composer",
        "url": "https://repo.magento.com/",
        "canonical": false
    }
},
```
And then run the same command:
```bash
composer require crowdsec/magento2-module-bouncer --no-plugins
```

As an alternative, you can also exclude the `crowdsec/magento2-module-bouncer` from the `repo.magento.com` repository:
```
"repositories": {
    "0": {
        "type": "composer",
        "url": "https://repo.magento.com/",
        "exclude": ["crowdsec/magento2-module-bouncer"]
    }
},
```

Thus, running `composer require crowdsec/magento2-module-bouncer` will always pick up the latest `y.y.y` Packagist release.

