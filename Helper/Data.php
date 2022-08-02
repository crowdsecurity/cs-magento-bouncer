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
use CrowdSecBouncer\BouncerException;
use CrowdSecBouncer\RestClient\ClientAbstract;
use LogicException;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\FileSystemException;
use Magento\Store\Model\ScopeInterface;
use CrowdSec\Bouncer\Logger\Logger;
use CrowdSec\Bouncer\Logger\Handlers\DebugFactory as DebugHandler;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\Filesystem\DirectoryList;

class Data extends Config
{
    /**
     * Logging instance
     * @var Logger
     */
    protected $_selfLogger;

    /**
     * @var DebugHandler
     */
    protected $_debugHandler;

    /**
     * @var Logger
     */
    protected $_finalLogger;

    /**
     * @var array
     */
    protected $_isEnabled = [];

    /** @var array */
    protected $_captchaWallConfigs = [];
    /** @var array */
    protected $_banWallConfigs = [];
    /**
     * @var array
     */
    protected $_bouncerConfigs;

    /**
     * @var string
     */
    protected $_forwardedFroIp;

    /**
     * Data constructor.
     * @param Logger $logger
     * @param DebugHandler $debugHandler
     * @param Context $context
     * @param Json $serializer
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Logger       $logger,
        DebugHandler $debugHandler,
        Context      $context,
        Json         $serializer,
        DirectoryList $directoryList
    ) {
        parent::__construct($context, $serializer, $directoryList);
        $this->_selfLogger = $logger;
        $this->_debugHandler = $debugHandler;
    }

    /**
     * Manage logger and its handlers
     *
     * @param array $configs
     * @return Logger
     * @throws LogicException
     */
    public function getFinalLogger(array $configs = []): Logger
    {
        if ($this->_finalLogger === null) {
            $this->_finalLogger = $this->_selfLogger;
            if ($this->isProdLogDisabled() || !empty($configs['disable_prod_log'])) {
                $this->_finalLogger->popHandler();
            }
            if ($this->isDebugLog() || !empty($configs['debug_mode'])) {
                $debugHandler = $this->_debugHandler->create();
                $this->_finalLogger->pushHandler($debugHandler);
            }
        }

        return $this->_finalLogger;
    }

    /**
     * Check if feature is enabled for some area
     *
     * @param string $areaCode
     * @return bool
     */
    public function isEnabled(string $areaCode = Area::AREA_FRONTEND): bool
    {
        if (!isset($this->_isEnabled[$areaCode])) {
            switch ($areaCode) {
                case Area::AREA_FRONTEND:
                    $this->_isEnabled[$areaCode] = $this->isFrontEnabled();
                    break;
                case Area::AREA_ADMINHTML:
                    $this->_isEnabled[$areaCode] = $this->isAdminEnabled();
                    break;
                case Area::AREA_WEBAPI_REST:
                case Area::AREA_WEBAPI_SOAP:
                case Area::AREA_GRAPHQL:
                    $this->_isEnabled[$areaCode] = $this->isApiEnabled();
                    break;
                default:
                    $this->_isEnabled[$areaCode] = false;
            }
        }

        return $this->_isEnabled[$areaCode];
    }

    /**
     * Retrieve a boolean setting
     *
     * @param string $path
     * @param string $scope
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    protected function _getBooleanSetting(string $path, string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue($path, $scope);
    }

    /**
     * Retrieve a string setting
     *
     * @param string $path
     * @param string $scope
     * @return string
     */
    protected function _getStringSetting(string $path, string $scope = ScopeInterface::SCOPE_STORE): string
    {
        return trim((string)$this->scopeConfig->getValue($path, $scope));
    }

    /**
     * Retrieve captcha wall settings
     *
     * @return array
     */
    public function getCaptchaWallConfigs(): array
    {
        if (empty($this->_captchaWallConfigs)) {
            $this->_captchaWallConfigs = [
                'hide_crowdsec_mentions' => $this->_getBooleanSetting(self::XML_PATH_ADVANCED_HIDE_MENTIONS),
                'color' => [
                    'text' => [
                        'primary' => $this->_getStringSetting(self::XML_PATH_THEME_COLOR_PRIMARY),
                        'secondary' => $this->_getStringSetting(self::XML_PATH_THEME_COLOR_SECOND),
                        'button' => $this->_getStringSetting(self::XML_PATH_THEME_COLOR_TEXT_BUTTON),
                        'error_message' => $this->_getStringSetting(self::XML_PATH_THEME_COLOR_TEXT_ERROR),
                    ],
                    'background' => [
                        'page' => $this->_getStringSetting(self::XML_PATH_THEME_COLOR_BG_PAGE),
                        'container' => $this->_getStringSetting(self::XML_PATH_THEME_COLOR_BG_CONTAINER),
                        'button' => $this->_getStringSetting(self::XML_PATH_THEME_COLOR_BG_BUTTON),
                        'button_hover' => $this->_getStringSetting(self::XML_PATH_THEME_COLOR_BG_BUTTON_HOVER),
                    ],
                ],
                'text' => [
                    'captcha_wall' => [
                        'tab_title' => $this->_getStringSetting(self::XML_PATH_THEME_CAPTCHA_TAB_TITLE),
                        'title' => $this->_getStringSetting(self::XML_PATH_THEME_CAPTCHA_TITLE),
                        'subtitle' => $this->_getStringSetting(self::XML_PATH_THEME_CAPTCHA_SUBTITLE),
                        'refresh_image_link' => $this->_getStringSetting(self::XML_PATH_THEME_CAPTCHA_REFRESH_LINK),
                        'captcha_placeholder' => $this->_getStringSetting(self::XML_PATH_THEME_CAPTCHA_PLACEHOLDER),
                        'send_button' => $this->_getStringSetting(self::XML_PATH_THEME_CAPTCHA_SEND_BUTTON),
                        'error_message' => $this->_getStringSetting(self::XML_PATH_THEME_CAPTCHA_ERROR_MESSAGE),
                        'footer' => $this->_getStringSetting(self::XML_PATH_THEME_CAPTCHA_FOOTER),
                    ],
                ],
                'custom_css' => $this->_getStringSetting(self::XML_PATH_THEME_CUSTOM_CSS),
            ];
        }

        return $this->_captchaWallConfigs;
    }

    /**
     * Retrieve ban wall settings
     *
     * @return array
     */
    public function getBanWallConfigs(): array
    {
        if (empty($this->_banWallConfigs)) {
            $this->_banWallConfigs = [
                'hide_crowdsec_mentions' => $this->_getBooleanSetting(self::XML_PATH_ADVANCED_HIDE_MENTIONS),
                'color' => [
                    'text' => [
                        'primary' => $this->_getStringSetting(self::XML_PATH_THEME_COLOR_PRIMARY),
                        'secondary' => $this->_getStringSetting(self::XML_PATH_THEME_COLOR_SECOND),
                        'error_message' => $this->_getStringSetting(self::XML_PATH_THEME_COLOR_TEXT_ERROR),
                    ],
                    'background' => [
                        'page' => $this->_getStringSetting(self::XML_PATH_THEME_COLOR_BG_PAGE),
                        'container' => $this->_getStringSetting(self::XML_PATH_THEME_COLOR_BG_CONTAINER),
                        'button' => $this->_getStringSetting(self::XML_PATH_THEME_COLOR_BG_BUTTON),
                        'button_hover' => $this->_getStringSetting(self::XML_PATH_THEME_COLOR_BG_BUTTON_HOVER),
                    ],
                ],
                'text' => [
                    'ban_wall' => [
                        'tab_title' => $this->_getStringSetting(self::XML_PATH_THEME_BAN_TAB_TITLE),
                        'title' => $this->_getStringSetting(self::XML_PATH_THEME_BAN_TITLE),
                        'subtitle' => $this->_getStringSetting(self::XML_PATH_THEME_BAN_SUBTITLE),
                        'footer' => $this->_getStringSetting(self::XML_PATH_THEME_BAN_FOOTER),
                    ],
                ],
                'custom_css' => $this->_getStringSetting(self::XML_PATH_THEME_CUSTOM_CSS),
            ];
        }

        return $this->_banWallConfigs;
    }

    /**
     * Get the current IP, even if it's the IP of a proxy
     *
     * @return string
     */
    public function getRemoteIp(): string
    {
        return $this->_remoteAddress->getRemoteAddress();
    }

    /**
     * Get the X-Forwarded-For IP
     *
     * @return string
     */
    public function getForwarderForIp(): string
    {
        if ($this->_forwardedFroIp === null) {
            $this->_forwardedFroIp = "";
            $XForwardedForHeader = $this->getHttpRequestHeader('X-Forwarded-For');
            if (null !== $XForwardedForHeader) {
                $ipList = array_map('trim', array_values(array_filter(explode(',', $XForwardedForHeader))));
                $this->_forwardedFroIp = end($ipList);
            }
        }

        return $this->_forwardedFroIp;
    }

    /**
     * Get the current HTTP method
     *
     * @return string
     */
    public function getHttpMethod(): string
    {
        return $this->_request->getMethod();
    }

    /**
     * Get the value of a posted field.
     *
     * @param string $name
     * @return string|null
     */
    public function getPostedVariable(string $name): ?string
    {
        $post = $this->_request->getPost($name);

        return $post ?: null;
    }

    /**
     * Get a http header by its name (Ex: "X-Forwarded-For")
     *
     * @param string $name
     * @return string|null
     */
    public function getHttpRequestHeader(string $name): ?string
    {
        $httpRequestHeader = $this->_request->getHeader($name);

        return $httpRequestHeader ?: null;
    }

    /**
     * Write a message on debug log file if debug log is enabled
     *
     * @param mixed $message
     * @param array $context
     * @return void
     * @throws LogicException
     */
    public function debug($message, array $context = []): void
    {
        if ($this->isDebugLog()) {
            $this->getFinalLogger()->debug($message, $context);
        }
    }

    /**
     * Write a critical message in prod log (and in debug log if enabled)
     *
     * @param mixed $message
     * @param array $context
     * @return void
     * @throws LogicException
     */
    public function critical($message, array $context = []): void
    {
        $this->getFinalLogger()->critical($message, $context);
    }

    /**
     * Write an error message in prod log (and in debug log if enabled)
     *
     * @param mixed $message
     * @param array $context
     * @return void
     * @throws LogicException
     */
    public function error($message, array $context = []): void
    {
        $this->getFinalLogger()->error($message, $context);
    }

    /**
     * Get cache system options
     *
     * @return array
     */
    public function getCacheSystemOptions(): array
    {
        return [
            Constants::CACHE_SYSTEM_PHPFS => __('File system'),
            Constants::CACHE_SYSTEM_REDIS => __('Redis'),
            Constants::CACHE_SYSTEM_MEMCACHED => __('Memcached')
        ];
    }

    /**
     * Generate a config array in order to instantiate a bouncer
     *
     * @return array
     * @throws LogicException
     * @throws FileSystemException|BouncerException
     */
    public function getBouncerConfigs(): array
    {
        if ($this->_bouncerConfigs === null) {
            $bouncingLevel = $this->getBouncingLevel();
            switch ($bouncingLevel) {
                case Constants::BOUNCING_LEVEL_DISABLED:
                    $maxRemediationLevel = Constants::REMEDIATION_BYPASS;
                    break;
                case Constants::BOUNCING_LEVEL_FLEX:
                    $maxRemediationLevel = Constants::REMEDIATION_CAPTCHA;
                    break;
                case Constants::BOUNCING_LEVEL_NORMAL:
                    $maxRemediationLevel = Constants::REMEDIATION_BAN;
                    break;
                default:
                    throw new BouncerException("Unknown $bouncingLevel");
            }

            $this->_bouncerConfigs = [
                // API connection
                'api_url' => $this->getApiUrl(),
                'api_key' => $this->getApiKey(),
                'api_user_agent' => Constants::BASE_USER_AGENT,
                'api_timeout' => Constants::API_TIMEOUT,
                'use_curl' => $this->isUseCurl(),
                // Debug
                'debug_mode' => $this->isDebugLog(),
                'disable_prod_log' => $this->isProdLogDisabled(),
                'log_directory_path' =>Constants::CROWDSEC_LOG_PATH,
                'forced_test_ip' => $this->getForcedTestIp(),
                'forced_test_forwarded_ip' => $this->getForcedTestForwardedIp(),
                'display_errors' => $this->canDisplayErrors(),
                // Bouncer behavior
                'bouncing_level' => $bouncingLevel,
                'trust_ip_forward_array' => $this->getTrustedForwardedIps(),
                'fallback_remediation' => $this->getRemediationFallback(),
                'max_remediation_level' => $maxRemediationLevel,
                // Cache
                'stream_mode' => $this->isStreamModeEnabled(),
                'cache_system' => $this->getCacheTechnology(),
                'fs_cache_path' => Constants::CROWDSEC_CACHE_PATH,
                'redis_dsn' => $this->getRedisDSN(),
                'memcached_dsn' => $this->getMemcachedDSN(),
                'clean_ip_cache_duration' => $this->getCleanIpCacheDuration(),
                'bad_ip_cache_duration' => $this->getBadIpCacheDuration(),
                'captcha_cache_duration' => $this->getCaptchaCacheDuration(),
                'geolocation_cache_duration' => $this->getGeolocationCacheDuration(),
                // Geolocation
                'geolocation' => $this->getGeolocation(),
            ];
        }

        return $this->_bouncerConfigs;
    }

    /**
     * Check if a cron expression is valid
     *
     * @param string $expr
     * @return void
     * @throws BouncerException
     * @see \Magento\Cron\Model\Schedule::setCronExpr
     */
    public function validateCronExpr(string $expr)
    {
        $e = preg_split('#\s+#', $expr, -1, PREG_SPLIT_NO_EMPTY);
        if (count($e) < 5 || count($e) > 6) {
            throw new BouncerException("Invalid cron expression: $expr");
        }
    }

    /**
     * Make a rest request
     *
     * @param ClientAbstract $restClient
     * @return void
     */
    public function ping(ClientAbstract $restClient)
    {
        $restClient->request('/v1/decisions', []);
    }
}
