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
use CrowdSec\Bouncer\Exception\CrowdSecException;
use CrowdSecBouncer\RestClient;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Area;
use Magento\Store\Model\ScopeInterface;
use CrowdSec\Bouncer\Logger\Logger;
use CrowdSec\Bouncer\Logger\Handlers\DebugFactory as DebugHandler;
use Magento\Framework\Serialize\Serializer\Json;

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
     * Final logger
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
     */
    public function __construct(
        Logger       $logger,
        DebugHandler $debugHandler,
        Context      $context,
        Json         $serializer
    ) {
        parent::__construct($context, $serializer);
        $this->_selfLogger = $logger;
        $this->_debugHandler = $debugHandler;
    }

    /**
     * Manage logger and its handlers
     * @return Logger
     */
    public function getFinalLogger(): Logger
    {
        if ($this->_finalLogger === null) {
            $this->_finalLogger = $this->_selfLogger;
            if ($this->isProdLogDisabled()) {
                $this->_finalLogger->popHandler();
            }
            if ($this->isDebugLog()) {
                $debugHandler = $this->_debugHandler->create();
                $this->_finalLogger->pushHandler($debugHandler);
            }
        }

        return $this->_finalLogger;
    }

    /**
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

    protected function _getBooleanSetting($path, $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue($path, $scope);
    }

    protected function _getStringSetting($path, $scope = ScopeInterface::SCOPE_STORE): string
    {
        return trim((string)$this->scopeConfig->getValue($path, $scope));
    }

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
     * @return string The current IP, even if it's the IP of a proxy
     */
    public function getRemoteIp(): string
    {
        return $this->_remoteAddress->getRemoteAddress();
    }

    /**
     * @return string The X-Forwarded-For IP
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
     * @return string The current HTTP method
     */
    public function getHttpMethod(): string
    {
        return $this->_request->getMethod();
    }

    /**
     * Get the value of a posted field.
     */
    public function getPostedVariable(string $name): ?string
    {
        $post = $this->_request->getPost($name);

        return $post ?: null;
    }

    /**
     * @return string Ex: "X-Forwarded-For"
     */
    public function getHttpRequestHeader(string $name): ?string
    {
        $httpRequestHeader = $this->_request->getHeader($name);

        return $httpRequestHeader ?: null;
    }

    /**
     * Write a message on debug log file if debug log is enabled
     * @param $message
     * @param array $context
     */
    public function debug($message, array $context = []): void
    {
        if ($this->isDebugLog()) {
            $this->getFinalLogger()->debug($message, $context);
        }
    }

    /**
     * Write a critical message in prod log (and in debug log if enabled)
     * @param $message
     * @param array $context
     */
    public function critical($message, array $context = []): void
    {
        $this->getFinalLogger()->critical($message, $context);
    }

    /**
     * Write an error message in prod log (and in debug log if enabled)
     * @param $message
     * @param array $context
     */
    public function error($message, array $context = []): void
    {
        $this->getFinalLogger()->error($message, $context);
    }

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
     * @return array
     * @throws CrowdSecException
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
                    throw new CrowdSecException("Unknown $bouncingLevel");
            }

            $logger = $this->getFinalLogger();

            $this->_bouncerConfigs = [
                // API connection
                'api_url' => $this->getApiUrl(),
                'api_key' => $this->getApiKey(),
                'api_user_agent' => Constants::BASE_USER_AGENT,
                'api_timeout' => Constants::API_TIMEOUT,
                // Debug
                'debug_mode' => $this->isDebugLog(),
                'log_directory_path' =>Constants::CROWDSEC_LOG_PATH,
                'forced_test_ip' => $this->getForcedTestIp(),
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
                'cache_expiration_for_clean_ip' => $this->getCleanIpCacheDuration(),
                'cache_expiration_for_bad_ip' => $this->getBadIpCacheDuration(),
                // Geolocation
                'geolocation' => [],
                // Extra configs
                'forced_cache_system' => null,
                'logger' => $logger,
            ];
        }

        return $this->_bouncerConfigs;
    }

    /**
     * @param string $expr
     *
     * @throws CrowdSecException
     * @see \Magento\Cron\Model\Schedule::setCronExpr
     */
    public function validateCronExpr(string $expr)
    {
        $e = preg_split('#\s+#', $expr, -1, PREG_SPLIT_NO_EMPTY);
        if (count($e) < 5 || count($e) > 6) {
            throw new CrowdSecException("Invalid cron expression: $expr");
        }
    }

    /**
     * @param RestClient $restClient
     * @param string $baseUri
     * @param string $userAgent
     * @param string $apiKey
     * @param int $timeout
     */
    public function ping(RestClient $restClient, string $baseUri, string $userAgent, string $apiKey, int $timeout = 1)
    {
        $restClient->configure($baseUri, [
            'User-Agent' => $userAgent,
            'X-Api-Key' => $apiKey,
            'Accept' => 'application/json',
        ], $timeout);

        $restClient->request('/v1/decisions', []);
    }
}
