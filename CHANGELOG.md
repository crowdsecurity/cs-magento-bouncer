# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.4.0] - 2022-08-11

### Added
- Add configuration to use `cURL` instead of `file_get_contents` to call LAPI.
- Add configuration `forced_test_forwarded_ip` for testing purpose.

## [1.3.0] - 2022-06-09

### Added
- Add configuration to set captcha flow cache lifetime
- Add configuration to set geolocation result cache lifetime
### Changed
- Use cache instead of session to store some values
### Fixed
- Fix wrong deleted decisions count during cache refresh

## [1.2.0] - 2022-05-12

### Added
- Add geolocation feature
- Add compatibility with Magento 2.4.4 and PHP 8.1


## [1.1.0] - 2022-03-11

### Added
- Add events log feature
### Fixed
- Fix primary and secondary text configuration path

## [1.0.0] - 2021-12-10

### Changed
- Modify default auto_prepend mode filename to avoid Magento 2 PHP code sniff error
- Update documentation

## [0.7.9] - 2021-11-19

### Added
- Initial release
