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
use CrowdSec\LapiClient\Bouncer as BouncerClient;
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
     * @var array
     */
    protected $_bouncerConfigs;
    /**
     * @var DebugHandler
     */
    protected $_debugHandler;

    /**
     * @var Logger
     */
    protected $_finalLogger;
    /**
     * @var string
     */
    protected $_forwardedFroIp;
    /**
     * @var array
     */
    protected $_isEnabled = [];
    /**
     * Logging instance
     * @var Logger
     */
    protected $_selfLogger;

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
     * Generate a config array in order to instantiate a bouncer
     *
     * @return array
     * @throws LogicException
     * @throws BouncerException
     */
    public function getBouncerConfigs(): array
    {
        if ($this->_bouncerConfigs === null) {

            $tlsConfigs = $this->getTLS();

            $this->_bouncerConfigs = [
                // API connection
                'api_url' => $this->getApiUrl(),
                'auth_type' => $this->getApiAuthType(),
                'tls_cert_path' => $tlsConfigs['tls_cert_path'] ?? "",
                'tls_key_path' => $tlsConfigs['tls_key_path'] ?? "",
                'tls_verify_peer' => $tlsConfigs['tls_verify_peer'] ?? false,
                'tls_ca_cert_path' => $tlsConfigs['tls_ca_cert_path'] ?? "",
                'api_key' => $this->getApiKey(),
                'user_agent_version' => Constants::VERSION,
                'user_agent_suffix' => 'Magento2',
                'api_timeout' => $this->getApiTimeout(),
                'api_connect_timeout' => $this->getApiConnectTimeout(),
                'use_curl' => $this->isUseCurl(),
                // Debug
                'debug_mode' => $this->isDebugLog(),
                'disable_prod_log' => $this->isProdLogDisabled(),
                'log_directory_path' => Constants::CROWDSEC_LOG_PATH,
                'forced_test_ip' => $this->getForcedTestIp(),
                'forced_test_forwarded_ip' => $this->getForcedTestForwardedIp(),
                'display_errors' => $this->canDisplayErrors(),
                // Bouncer behavior
                'bouncing_level' => $this->getBouncingLevel(),
                'trust_ip_forward_array' => $this->getTrustedForwardedIps(),
                'fallback_remediation' => $this->getRemediationFallback(),
                // Cache
                'stream_mode' => $this->isStreamModeEnabled(),
                'cache_system' => $this->getCacheTechnology(),
                'fs_cache_path' => Constants::CROWDSEC_CACHE_PATH,
                'redis_dsn' => $this->getRedisDSN(),
                'memcached_dsn' => $this->getMemcachedDSN(),
                'clean_ip_cache_duration' => $this->getCleanIpCacheDuration(),
                'bad_ip_cache_duration' => $this->getBadIpCacheDuration(),
                'captcha_cache_duration' => $this->getCaptchaCacheDuration(),
                // Geolocation
                'geolocation' => $this->getGeolocation(),
                'hide_mentions' => $this->_getBooleanSetting(self::XML_PATH_ADVANCED_HIDE_MENTIONS),
                'custom_css' => $this->_getStringSetting(self::XML_PATH_THEME_CUSTOM_CSS),
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
                    'ban_wall' => [
                        'tab_title' => $this->_getStringSetting(self::XML_PATH_THEME_BAN_TAB_TITLE),
                        'title' => $this->_getStringSetting(self::XML_PATH_THEME_BAN_TITLE),
                        'subtitle' => $this->_getStringSetting(self::XML_PATH_THEME_BAN_SUBTITLE),
                        'footer' => $this->_getStringSetting(self::XML_PATH_THEME_BAN_FOOTER),
                    ],
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

            ];
        }

        return $this->_bouncerConfigs;
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
     * Get connexion options
     *
     * @return array
     */
    public function getConnexionOptions(): array
    {
        return [
            Constants::AUTH_KEY => __('API key'),
            Constants::AUTH_TLS => __('TLS'),
        ];
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
     * Get the current IP, even if it's the IP of a proxy
     *
     * @return string
     */
    public function getRemoteIp(): string
    {
        return $this->_remoteAddress->getRemoteAddress();
    }

    /**
     * Return the URI for this request object as a string
     *
     * @return string
     */
    public function getRequestUri(): string
    {
        return $this->_request->getUriString();
    }

    /**
     * Write an info message in prod log (and in debug log if enabled)
     *
     * @param mixed $message
     * @param array $context
     * @return void
     * @throws LogicException
     */
    public function info($message, array $context = []): void
    {
        $this->getFinalLogger()->info($message, $context);
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
     * Make a rest request
     *
     * @param BouncerClient $restClient
     * @return void
     */
    public function ping(BouncerClient $restClient)
    {
        $restClient->getFilteredDecisions();
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
}
