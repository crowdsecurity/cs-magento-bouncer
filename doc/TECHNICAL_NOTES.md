![CrowdSec Logo](images/logo_crowdsec.png)

# CrowdSec Bouncer extension for Magento 2

## Technical notes

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**

- [Full Page Cache](#full-page-cache)
- [Varnish](#varnish)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

## CrowdSec Bouncer PHP Library

This extension is mainly based on the CrowdSec Bouncer PHP library. It is an open source library whose code you can find
[here](https://github.com/crowdsecurity/php-cs-bouncer).

## Full Page Cache

In Magento 2, the full page cache is implemented via a plugin on the front controller (`vendor/magento/module-page-cache/Model/App/FrontController/BuiltinPlugin.php::aroundDispatch`). 

As we want to 
check IPs before this FPC process, we use also an `aroundDispatch` plugin, and we adjust the `sortOrder` so that 
the 
CrowdSec_Bouncer plugin is called before the FPC plugin: 

* In `vendor/crowdsec/magento2-module-bouncer/etc/frontend/di.xml` we change the `sortOrder` of the Magento FPC plugin.
* In `vendor/crowdsec/magento2-module-bouncer/etc/di.xml` we declare our plugin with a lower `sortOrder`.

Note that our plugin acts on every Magento areas while the FPC plugin is just active on the frontend area.

## Varnish

This extension works with a Varnish cached Magento 2 instance but, as the cached content of page are delivered by 
Varnish itself, we adopt the following strategy : 

* If the first visit of a non cached page comes from a bad IP, we display the captcha or ban wall, but we do not add 
  this 
  content to the cache. See `vendor/crowdsec/magento2-module-bouncer/Model/Bouncer.php::sendResponse`.

* If the first visit of a non cached page comes from a clean IP, we let it pass, so Magento will cache the content 
  as usual. As a result, if a bad IP user visits this page after the clean IP one, he will see the cached content.


