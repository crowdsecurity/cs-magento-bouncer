<?php declare(strict_types=1);
/**
 * Crowdsec_Bouncer Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT LICENSE
 * that is bundled with this package in the file LICENSE
 *
 * @category   Crowdsec
 * @package    Crowdsec_Bouncer
 * @copyright  Copyright (c)  2021+ CrowdSec
 * @author     CrowdSec team
 * @see        https://crowdsec.net CrowdSec Official Website
 * @license    MIT LICENSE
 *
 */

/**
 *
 * @category Crowdsec
 * @package  Crowdsec_Bouncer
 * @module   Bouncer
 * @author   CrowdSec team
 *
 */

namespace Crowdsec\Bouncer\Helper;

use Crowdsec\Bouncer\Constants;
use Crowdsec\Bouncer\Exception\CrowdsecException;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Area;
use Magento\Store\Model\ScopeInterface;
use Crowdsec\Bouncer\Logger\Logger;
use Crowdsec\Bouncer\Logger\Handlers\DebugFactory as DebugHandler;
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
     * @var bool
     */
    protected $_isEnabled;

    /** @var array */
    protected $_captchaWallConfigs = [];
    /** @var array */
    protected $_banWallConfigs = [];
    /**
     * @var array
     */
    protected $_bouncerConfigs;

    /**
     * Data constructor.
     * @param Logger $logger
     * @param DebugHandler $debugHandler
     * @param Context $context
     * @param Json $serializer
     */
    public function __construct(
        Logger $logger,
        DebugHandler $debugHandler,
        Context $context,
        Json $serializer
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
    public function isEnabled($areaCode = Area::AREA_FRONTEND): bool
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
        return trim($this->scopeConfig->getValue($path, $scope) ?: "");
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
     * Write a message on debug log file if debug log is enabled
     * @param $message
     * @param array $context
     */
    public function debug($message, $context = []): void
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
    public function critical($message, $context = []): void
    {
        $this->getFinalLogger()->critical($message, $context);
    }

    /**
     * Write an error message in prod log (and in debug log if enabled)
     * @param $message
     * @param array $context
     */
    public function error($message, $context = []): void
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
     * @throws CrowdsecException
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
                    throw new CrowdsecException(__("Unknown $bouncingLevel"));
            }

            $this->_bouncerConfigs = [
                'api_url' => $this->getApiUrl(),
                'api_key' => $this->getApiKey(),
                'api_user_agent' => Constants::BASE_USER_AGENT,
                'live_mode' => !$this->isStreamModeEnabled(),
                'clean_ip_duration' => $this->getCleanIpCacheDuration(),
                'bad_ip_duration' => $this->getBadIpCacheDuration(),
                'fallback_remediation' => $this->getRemediationFallback(),
                'max_remediation_level' => $maxRemediationLevel,
                'logger' => $this->getFinalLogger(),
                'cache_system' => $this->getCacheTechnology(),
                'memcached_dsn' => $this->getMemcachedDSN(),
                'redis_dsn' => $this->getRedisDSN(),
                'fs_cache_path' => Constants::CROWDSEC_CACHE_PATH,
                'forced_cache_system' => null,
                'display_errors' => $this->canDisplayErrors()
            ];
        }

        return $this->_bouncerConfigs;
    }
}
