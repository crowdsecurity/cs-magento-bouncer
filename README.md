# CrowdSec_Bouncer

----------------------

```
@category   CrowdSec  
@package    CrowdSec_Bouncer  
@author     CrowdSec team
@see        https://crowdsec.net CrowdSec Official Website 
@copyright  Copyright (c)  2021+ CrowdSec  
@license    MIT LICENSE
  
```

<!-- START doctoc -->
<!-- END doctoc -->

## Description


## Installation

### Prerequisites

This bouncer has been designed to protect Magento 2 hosted websites from all kinds of attacks. To be able to use this 
blocker,
the first step is to install [CrowdSec v1](https://doc.crowdsec.net/Crowdsec/v1/getting_started/installation/).

Please note that first and foremost CrowdSec must be installed on a server that is accessible via the Magento 2 site. 
Remember: CrowdSec detects, bouncers deter.

### Requirements

- CrowdSec v1
- Magento >= 2.3


### Installation methods


Use `Composer` by simply adding `crowdsec/magento2-module-bouncer` as a dependency:

       composer require crowdsec/magento2-module-bouncer


## Post Installation

### Enable the module

After the installment of the module source code, the module has to be enabled by the Magento® 2 CLI.

    bin/magento module:enable CrowdSec_Bouncer

### System Upgrade

After enabling the module, the Magento® 2 system must be upgraded.

If the system mode is set to production, run the compile command first. This is not necessary for the developer mode.

    bin/magento setup:di:compile

Then run the upgrade command:

    bin/magento setup:upgrade
    
### Clear Cache

The Magento® 2 cache should be cleared by running the flush command.

    bin/magento cache:flush

Sometimes, other cache systems or services must be restarted first, e.g. Apache Webserver and PHP FPM.

### Deploy static content

At last, you have to deploy the static content:

    bin/magento setup:static-content:deploy -f


## Usage

### Features



### Configurations

This module comes with the following configurations:



## Technical Notes


### Coding Standards

This extension has been checked with the [Magento Extension Quality Program Coding Standard](https://github.com/magento/magento-coding-standard).


## License

[MIT](LICENSE)
