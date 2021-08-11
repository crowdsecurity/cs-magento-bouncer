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
