# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

The [public API](https://semver.org/spec/v2.0.0.html#spec-item-1) for this project is defined by the set of
functions provided by the module.


## [2.1.0](https://github.com/crowdsecurity/cs-magento-bouncer/releases/tag/v2.1.0) - 2024-01-05
[_Compare with previous release_](https://github.com/crowdsecurity/cs-magento-bouncer/compare/v2.0.0...v2.1.0)


### Changed

- Encrypt bouncer key in database

### Removed

- Removed Events log feature

### Added

- Add `api_connect_timeout` configuration for `Curl` request handler
- Add `api_timeout` configuration

### Fixed

- Allow `crowdsec/symfony-cache:3.0.0` dependency to avoid composer conflict with some Magento 2.4.6 patch versions

---


## [2.0.0](https://github.com/crowdsecurity/cs-magento-bouncer/releases/tag/v2.0.0) - 2023-03-23
[_Compare with previous release_](https://github.com/crowdsecurity/cs-magento-bouncer/compare/v1.5.0...v2.0.0)

### Changed

- All source code has been refactored using new CrowdSec PHP librairies:
    - Logs messages have been changed
    - User Agent sent to CrowdSec LAPI has been changed to `csphplapi_Magento2/vX.Y.Z`
- Change composer minimum stability from `dev` to `stable`

### Added

- Add compatibility with Magento 2.4.6 and PHP 8.2

---


## [1.5.0](https://github.com/crowdsecurity/cs-magento-bouncer/releases/tag/v1.5.0) - 2022-09-08
[_Compare with previous release_](https://github.com/crowdsecurity/cs-magento-bouncer/compare/v1.4.0...v1.5.0)
### Added
- Add TLS authentication feature
---

## [1.4.0](https://github.com/crowdsecurity/cs-magento-bouncer/releases/tag/v1.4.0) - 2022-08-11
[_Compare with previous release_](https://github.com/crowdsecurity/cs-magento-bouncer/compare/v1.3.0...v1.4.0)
### Added
- Add configuration to use `cURL` instead of `file_get_contents` to call LAPI.
- Add configuration `forced_test_forwarded_ip` for testing purpose.
---
## [1.3.0](https://github.com/crowdsecurity/cs-magento-bouncer/releases/tag/v1.3.0) - 2022-06-09
[_Compare with previous release_](https://github.com/crowdsecurity/cs-magento-bouncer/compare/v1.2.0...v1.3.0)
### Added
- Add configuration to set captcha flow cache lifetime
- Add configuration to set geolocation result cache lifetime
### Changed
- Use cache instead of session to store some values
### Fixed
- Fix wrong deleted decisions count during cache refresh
---
## [1.2.0](https://github.com/crowdsecurity/cs-magento-bouncer/releases/tag/v1.2.0) - 2022-05-12
[_Compare with previous release_](https://github.com/crowdsecurity/cs-magento-bouncer/compare/v1.1.0...v1.2.0)
### Added
- Add geolocation feature
- Add compatibility with Magento 2.4.4 and PHP 8.1

---
## [1.1.0](https://github.com/crowdsecurity/cs-magento-bouncer/releases/tag/v1.1.0) - 2022-03-11
[_Compare with previous release_](https://github.com/crowdsecurity/cs-magento-bouncer/compare/v1.0.0...v1.1.0)
### Added
- Add events log feature
### Fixed
- Fix primary and secondary text configuration path
---
## [1.0.0](https://github.com/crowdsecurity/cs-magento-bouncer/releases/tag/v1.0.0) - 2021-12-10
[_Compare with previous release_](https://github.com/crowdsecurity/cs-magento-bouncer/compare/v0.7.9...v1.0.0)
### Changed
- Modify default auto_prepend mode filename to avoid Magento 2 PHP code sniff error
- Update documentation
---
## [0.7.9](https://github.com/crowdsecurity/cs-magento-bouncer/releases/tag/v0.7.9) - 2021-11-19

### Added
- Initial release
