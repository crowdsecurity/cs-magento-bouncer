<?php declare(strict_types=1);
/**
 * CrowdSec_Bouncer Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT LICENSE
 * that is bundled with this package in the file LICENSE
 *
 * @category   CrowdSec
 * @package    CrowdSec_Bouncer
 * @copyright  Copyright (c)  2021+ CrowdSec
 * @author     CrowdSec team
 * @see        https://crowdsec.net CrowdSec Official Website
 * @license    MIT LICENSE
 *
 */

/**
 *
 * @category CrowdSec
 * @package  CrowdSec_Bouncer
 * @module   Bouncer
 * @author   CrowdSec team
 *
 */

namespace CrowdSec\Bouncer\Helper;

use CrowdSec\Bouncer\Constants;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\Helper\Context;

class Config extends AbstractHelper
{
    const SECTION = 'crowdsec_bouncer';
    const API_URL_FULL_PATH = 'groups/general/groups/connection/fields/api_url/value';
    const API_KEY_FULL_PATH = 'groups/general/groups/connection/fields/api_key/value';
    const MEMCACHED_DSN_FULL_PATH = 'groups/advanced/groups/cache/fields/memcached_dsn/value';
    const REDIS_DSN_FULL_PATH = 'groups/advanced/groups/cache/fields/redis_dsn/value';
    const CACHE_TECHNOLOGY_FULL_PATH = 'groups/advanced/groups/cache/fields/technology/value';
    const STREAM_MODE_FULL_PATH = 'groups/advanced/groups/mode/fields/stream/value';
    const REFRESH_CRON_EXPR_FULL_PATH = 'groups/advanced/groups/mode/fields/refresh_cron_expr/value';
    const PRUNE_CRON_EXPR_FULL_PATH = 'groups/advanced/groups/cache/fields/prune_cron_expr/value';

    // General configs
    const XML_PATH_API_URL = self::SECTION . '/general/connection/api_url';
    const XML_PATH_API_KEY = self::SECTION . '/general/connection/api_key';
    const XML_PATH_FRONT_ENABLED = self::SECTION . '/general/bouncing/front_enabled';
    const XML_PATH_ADMIN_ENABLED = self::SECTION . '/general/bouncing/admin_enabled';
    const XML_PATH_API_ENABLED = self::SECTION . '/general/bouncing/api_enabled';
    const XML_PATH_BOUNCING_LEVEL = self::SECTION . '/general/bouncing/level';
    // Theme configs
    const XML_PATH_THEME_CAPTCHA_TAB_TITLE = self::SECTION . '/theme/captcha/wall_tab_title';
    const XML_PATH_THEME_CAPTCHA_TITLE = self::SECTION . '/theme/captcha/wall_title';
    const XML_PATH_THEME_CAPTCHA_SUBTITLE = self::SECTION . '/theme/captcha/wall_subtitle';
    const XML_PATH_THEME_CAPTCHA_REFRESH_LINK = self::SECTION . '/theme/captcha/wall_refresh_image_link';
    const XML_PATH_THEME_CAPTCHA_PLACEHOLDER = self::SECTION . '/theme/captcha/wall_input_placeholder';
    const XML_PATH_THEME_CAPTCHA_SEND_BUTTON = self::SECTION . '/theme/captcha/wall_send_button';
    const XML_PATH_THEME_CAPTCHA_ERROR_MESSAGE = self::SECTION . '/theme/captcha/wall_error_message';
    const XML_PATH_THEME_CAPTCHA_FOOTER = self::SECTION . '/theme/captcha/wall_footer';
    const XML_PATH_THEME_BAN_TAB_TITLE = self::SECTION . '/theme/ban/wall_tab_title';
    const XML_PATH_THEME_BAN_TITLE = self::SECTION . '/theme/ban/wall_title';
    const XML_PATH_THEME_BAN_SUBTITLE = self::SECTION . '/theme/ban/wall_subtitle';
    const XML_PATH_THEME_BAN_FOOTER = self::SECTION . '/theme/ban/wall_footer';
    const XML_PATH_THEME_CUSTOM_CSS = self::SECTION . '/theme/css/custom';
    const XML_PATH_THEME_COLOR_PRIMARY = self::SECTION . '/theme/color/text_primary';
    const XML_PATH_THEME_COLOR_SECOND = self::SECTION . '/theme/color/text_secondary';
    const XML_PATH_THEME_COLOR_TEXT_BUTTON = self::SECTION . '/theme/color/text_button';
    const XML_PATH_THEME_COLOR_TEXT_ERROR = self::SECTION . '/theme/color/text_error_message';
    const XML_PATH_THEME_COLOR_BG_PAGE = self::SECTION . '/theme/color/background_page';
    const XML_PATH_THEME_COLOR_BG_CONTAINER = self::SECTION . '/theme/color/background_container';
    const XML_PATH_THEME_COLOR_BG_BUTTON = self::SECTION . '/theme/color/background_button';
    const XML_PATH_THEME_COLOR_BG_BUTTON_HOVER = self::SECTION . '/theme/color/background_button_hover';
    // Advanced configs
    const XML_PATH_ADVANCED_HIDE_MENTIONS = self::SECTION . '/advanced/remediation/hide_mentions';
    const XML_PATH_ADVANCED_REMEDIATION_FALLBACK = self::SECTION . '/advanced/remediation/fallback';
    const XML_PATH_ADVANCED_MODE_STREAM = self::SECTION . '/advanced/mode/stream';
    const XML_PATH_ADVANCED_REFRESH_CRON_EXPR = self::SECTION . '/advanced/mode/refresh_cron_expr';
    const XML_PATH_ADVANCED_PRUNE_CRON_EXPR = self::SECTION . '/advanced/cache/prune_cron_expr';
    const XML_PATH_ADVANCED_CACHE_TECHNOLOGY = self::SECTION . '/advanced/cache/technology';
    const XML_PATH_ADVANCED_CACHE_REDIS_DSN = self::SECTION . '/advanced/cache/redis_dsn';
    const XML_PATH_ADVANCED_CACHE_MEMCACHED_DSN = self::SECTION . '/advanced/cache/memcached_dsn';
    const XML_PATH_ADVANCED_CACHE_CLEAN = self::SECTION . '/advanced/cache/clean_ip_cache_duration';
    const XML_PATH_ADVANCED_CACHE_BAD = self::SECTION . '/advanced/cache/bad_ip_cache_duration';
    const XML_PATH_ADVANCED_DEBUG_LOG = self::SECTION . '/advanced/debug/log';
    const XML_PATH_ADVANCED_DISPLAY_ERRORS = self::SECTION . '/advanced/debug/display_errors';
    const XML_PATH_ADVANCED_DISABLE_PROD_LOG = self::SECTION . '/advanced/debug/disable_prod_log';
    const XML_PATH_ADVANCED_FORCED_TEST_IP = self::SECTION . '/advanced/debug/forced_test_ip';

    const XML_PATH_ADVANCED_GEOLOCATION_ENABLED = self::SECTION . '/advanced/geolocation/enabled';
    const XML_PATH_ADVANCED_GEOLOCATION_TYPE = self::SECTION . '/advanced/geolocation/type';
    const XML_PATH_ADVANCED_GEOLOCATION_SAVE_SESSION = self::SECTION . '/advanced/geolocation/save_in_session';
    const XML_PATH_ADVANCED_GEOLOCATION_MAXMIND_DB_TYPE = self::SECTION . '/advanced/geolocation/maxmind_database_type';
    const XML_PATH_ADVANCED_GEOLOCATION_MAXMIND_DB_PATH = self::SECTION . '/advanced/geolocation/maxmind_database_path';

    // Events configs
    const XML_PATH_EVENTS_LOG_ROOT = self::SECTION . '/events/log/';

    /**
     * The path of trusted forward ips as array setting
     */
    const TRUSTED_FORWARD_IPS_PATH = self::SECTION . '/advanced/remediation/trust_ip_forward_array';

    const TEXT_SEPARATOR = ',';

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    protected $_globals = [
        'api_url' => null,
        'api_key' => null,
        'is_admin_enabled' => null,
        'is_api_enabled' => null,
        'is_debug_log' => null,
        'forced_test_ip' => null,
        'can_display_errors' => null,
        'is_prod_log_disabled' => null,
        'is_events_log_enabled' => [],
        'is_stream_mode' => null,
        'refresh_cron_expr' => null,
        'prune_cron_expr' => null,
        'cache_technology' => null,
        'redis_dsn' => null,
        'memcached_dsn' => null,
        'clean_ip_duration' => null,
        'bad_ip_duration' => null,
        'trusted_forwarded_ip' => null,
        'geolocation' => null,
    ];

    protected $_storeviews = [
        'is_front_enabled' => null,
        'bouncing_level' => null,
        'remediation_fallback' => null
    ];

    /**
     * Data constructor.
     * @param Context $context
     * @param Json $serializer
     */
    public function __construct(
        Context $context,
        Json $serializer,
        DirectoryList $directoryList
    ) {
        parent::__construct($context);
        $this->serializer = $serializer;
        $this->directoryList = $directoryList;
    }

    /**
     * Get api url config
     * @return string
     */
    public function getApiUrl(): string
    {
        if (!isset($this->_globals['api_url'])) {
            $this->_globals['api_url'] = trim((string)$this->scopeConfig->getValue(self::XML_PATH_API_URL));
        }

        return (string) $this->_globals['api_url'];
    }

    /**
     * Get api key config
     * @return string
     */
    public function getApiKey(): string
    {
        if (!isset($this->_globals['api_key'])) {
            $this->_globals['api_key'] = trim((string)$this->scopeConfig->getValue(self::XML_PATH_API_KEY));
        }

        return (string) $this->_globals['api_key'];
    }

    /**
     * Get enabled config for front
     * @return bool
     */
    public function isFrontEnabled(): bool
    {
        if (!isset($this->_storeviews['is_front_enabled'])) {
            $this->_storeviews['is_front_enabled'] = (bool)$this->scopeConfig->getValue(
                self::XML_PATH_FRONT_ENABLED,
                ScopeInterface::SCOPE_STORE
            );
        }

        return (bool) $this->_storeviews['is_front_enabled'];
    }

    /**
     * Get enabled config for admin
     * @return bool
     */
    public function isAdminEnabled(): bool
    {
        if (!isset($this->_globals['is_admin_enabled'])) {
            $this->_globals['is_admin_enabled'] = (bool)$this->scopeConfig->getValue(self::XML_PATH_ADMIN_ENABLED);
        }

        return (bool) $this->_globals['is_admin_enabled'];
    }

    /**
     * Get enabled config for api
     * @return bool
     */
    public function isApiEnabled(): bool
    {
        if (!isset($this->_globals['is_api_enabled'])) {
            $this->_globals['is_api_enabled'] = (bool)$this->scopeConfig->getValue(self::XML_PATH_API_ENABLED);
        }

        return (bool) $this->_globals['is_api_enabled'];
    }

    /**
     * Get debug log enabled config
     * @return bool
     */
    public function isDebugLog(): bool
    {
        if (!isset($this->_globals['is_debug_log'])) {
            $this->_globals['is_debug_log'] = (bool)$this->scopeConfig->getValue(self::XML_PATH_ADVANCED_DEBUG_LOG);
        }

        return (bool) $this->_globals['is_debug_log'];
    }

    /**
     * Get prod log deactivation config
     * @return bool
     */
    public function isProdLogDisabled(): bool
    {
        if (!isset($this->_globals['is_prod_log_disabled'])) {
            $this->_globals['is_prod_log_disabled'] =
                (bool)$this->scopeConfig->getValue(self::XML_PATH_ADVANCED_DISABLE_PROD_LOG);
        }

        return (bool) $this->_globals['is_prod_log_disabled'];
    }

    /**
     * Get events log enabled config
     * @param string $process
     * @return bool
     */
    public function isEventsLogEnabled(string $process): bool
    {
        if (!isset($this->_globals['is_events_log_enabled'][$process])) {
            $enabled = (bool)$this->scopeConfig->getValue(self::XML_PATH_EVENTS_LOG_ROOT . 'enabled');
            $this->_globals['is_events_log_enabled'][$process] =
                $enabled && $this->scopeConfig->getValue(self::XML_PATH_EVENTS_LOG_ROOT . $process);
        }

        return (bool) $this->_globals['is_events_log_enabled'][$process];
    }

    /**
     * Get display errors config
     * @return bool
     */
    public function canDisplayErrors(): bool
    {
        if (!isset($this->_globals['can_display_errors'])) {
            $this->_globals['can_display_errors'] =
                (bool)$this->scopeConfig->getValue(self::XML_PATH_ADVANCED_DISPLAY_ERRORS);
        }

        return (bool) $this->_globals['can_display_errors'];
    }

    /**
     * Get bouncing level config
     * @return string
     */
    public function getBouncingLevel(): string
    {
        if (!isset($this->_storeviews['bouncing_level'])) {
            $this->_storeviews['bouncing_level'] = $this->scopeConfig->getValue(
                self::XML_PATH_BOUNCING_LEVEL,
                ScopeInterface::SCOPE_STORE
            );
        }

        return (string) $this->_storeviews['bouncing_level'];
    }

    /**
     * Get geolocation config
     * @return array
     */
    public function getGeolocation(): array
    {
        if (!isset($this->_globals['geolocation'])) {
            $result = ['enabled' => false];
            if ($this->scopeConfig->getValue(self::XML_PATH_ADVANCED_GEOLOCATION_ENABLED)) {
                $result['enabled'] = true;
                $result['save_in_session'] =
                    (bool)$this->scopeConfig->getValue(self::XML_PATH_ADVANCED_GEOLOCATION_SAVE_SESSION);
                $type = (string)$this->scopeConfig->getValue(self::XML_PATH_ADVANCED_GEOLOCATION_TYPE);
                $result['type'] = $type;
                if ($type === Constants::GEOLOCATION_TYPE_MAXMIND) {
                    $result[$type]['database_type'] =
                        (string)$this->scopeConfig->getValue(self::XML_PATH_ADVANCED_GEOLOCATION_MAXMIND_DB_TYPE);
                    $result[$type]['database_path'] = $this->directoryList->getPath(DirectoryList::VAR_DIR) . DS .
                        ltrim($this->scopeConfig->getValue(self::XML_PATH_ADVANCED_GEOLOCATION_MAXMIND_DB_PATH), '/');
                }
            }

            $this->_globals['geolocation'] = $result;
        }

        return (array)$this->_globals['geolocation'];
    }

    /**
     * Get forced test ip config
     * @return string
     */
    public function getForcedTestIp(): string
    {
        if (!isset($this->_globals['forced_test_ip'])) {
            $this->_globals['forced_test_ip'] = (string)$this->scopeConfig->getValue(
                self::XML_PATH_ADVANCED_FORCED_TEST_IP
            );
        }

        return (string) $this->_globals['forced_test_ip'];
    }

    /**
     * Get stream mode config
     * @return bool
     */
    public function isStreamModeEnabled(): bool
    {
        if (!isset($this->_globals['is_stream_mode'])) {
            $this->_globals['is_stream_mode'] = (bool)$this->scopeConfig->getValue(self::XML_PATH_ADVANCED_MODE_STREAM);
        }

        return (bool) $this->_globals['is_stream_mode'];
    }

    /**
     * Get refresh cron schedule expression config
     * @return string
     */
    public function getRefreshCronExpr(): string
    {
        if (!isset($this->_globals['refresh_cron_expr'])) {
            $this->_globals['refresh_cron_expr'] = (string)$this->scopeConfig->getValue(
                self::XML_PATH_ADVANCED_REFRESH_CRON_EXPR
            );
        }

        return (string) $this->_globals['refresh_cron_expr'];
    }

    /**
     * Get pruning cron schedule expression config
     * @return string
     */
    public function getPruneCronExpr(): string
    {
        if (!isset($this->_globals['prune_cron_expr'])) {
            $this->_globals['prune_cron_expr'] = (string)$this->scopeConfig->getValue(
                self::XML_PATH_ADVANCED_PRUNE_CRON_EXPR
            );
        }

        return (string) $this->_globals['prune_cron_expr'];
    }

    /**
     * Get cache technology config
     * @return string
     */
    public function getCacheTechnology(): string
    {
        if (!isset($this->_globals['cache_technology'])) {
            $this->_globals['cache_technology'] = (string)$this->scopeConfig->getValue(
                self::XML_PATH_ADVANCED_CACHE_TECHNOLOGY
            );
        }

        return (string) $this->_globals['cache_technology'];
    }

    /**
     * Get Redis DSN config
     * @return string
     */
    public function getRedisDSN(): string
    {
        if (!isset($this->_globals['redis_dsn'])) {
            $this->_globals['redis_dsn'] = (string)$this->scopeConfig->getValue(
                self::XML_PATH_ADVANCED_CACHE_REDIS_DSN
            );
        }

        return (string) $this->_globals['redis_dsn'];
    }

    /**
     * Get Memcached DSN config
     * @return string
     */
    public function getMemcachedDSN(): string
    {
        if (!isset($this->_globals['memcached_dsn'])) {
            $this->_globals['memcached_dsn'] = (string)$this->scopeConfig->getValue(
                self::XML_PATH_ADVANCED_CACHE_MEMCACHED_DSN
            );
        }

        return (string) $this->_globals['memcached_dsn'];
    }

    /**
     * Get clean ip cache duration config
     * @return int
     */
    public function getCleanIpCacheDuration(): int
    {
        if (!isset($this->_globals['clean_ip_duration'])) {
            $this->_globals['clean_ip_duration'] = (int)$this->scopeConfig->getValue(
                self::XML_PATH_ADVANCED_CACHE_CLEAN
            );
        }

        return (int) $this->_globals['clean_ip_duration'];
    }

    /**
     * Get bad ip cache duration config
     * @return int
     */
    public function getBadIpCacheDuration(): int
    {
        if (!isset($this->_globals['bad_ip_duration'])) {
            $this->_globals['bad_ip_duration'] = (int)$this->scopeConfig->getValue(
                self::XML_PATH_ADVANCED_CACHE_BAD
            );
        }

        return (int) $this->_globals['bad_ip_duration'];
    }

    /**
     * Get bouncing level config
     * @return string
     */
    public function getRemediationFallback(): string
    {
        if (!isset($this->_storeviews['remediation_fallback'])) {
            $this->_storeviews['remediation_fallback'] = $this->scopeConfig->getValue(
                self::XML_PATH_ADVANCED_REMEDIATION_FALLBACK,
                ScopeInterface::SCOPE_STORE
            );
        }

        return (string) $this->_storeviews['remediation_fallback'];
    }

    /**
     * Get trusted forwarded ips config
     * @return array
     */
    public function getTrustedForwardedIps(): array
    {
        if (!isset($this->_globals['trusted_forwarded_ip'])) {
            $trustedForwardedIps = $this->scopeConfig->getValue(self::TRUSTED_FORWARD_IPS_PATH);

            $this->_globals['trusted_forwarded_ip'] =
                !empty($trustedForwardedIps) ? $this->serializer->unserialize($trustedForwardedIps) : [];
        }

        return (array) $this->_globals['trusted_forwarded_ip'];
    }
}
