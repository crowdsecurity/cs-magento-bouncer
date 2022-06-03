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

namespace CrowdSec\Bouncer\Model;

use CrowdSec\Bouncer\Exception\CrowdSecException;
use Exception;
use Magento\Framework\App\Response\Http;
use CrowdSec\Bouncer\Helper\Data as Helper;
use CrowdSecBouncer\AbstractBounce;
use CrowdSecBouncer\IBounce;
use CrowdSecBouncer\Bouncer as BouncerInstance;
use CrowdSecBouncer\BouncerFactory;
use Psr\Cache\InvalidArgumentException;

/**
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Bouncer extends AbstractBounce implements IBounce
{

    /**
     * @var Http
     */
    protected $response;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var CacheFactory
     */
    protected $cacheFactory;

    /** @var BouncerInstance */
    protected $bouncerInstance;

    /** @var  BouncerFactory */
    protected $bouncerInstanceFactory;

    /** @var bool */
    protected $remediationDisplay = false;

    /**
     * Constructor
     *
     * @param Http $response
     * @param Helper $helper
     * @param CacheFactory $cacheFactory
     * @param BouncerFactory $bouncerInstanceFactory
     */
    public function __construct(
        Http $response,
        Helper $helper,
        CacheFactory $cacheFactory,
        BouncerFactory $bouncerInstanceFactory
    ) {
        $this->response = $response;
        $this->helper = $helper;
        $this->cacheFactory = $cacheFactory;
        $this->bouncerInstanceFactory = $bouncerInstanceFactory;
    }

    /**
     * Init the logger.
     */
    public function initLogger(): void
    {
        $this->logger = $this->helper->getFinalLogger();
    }

    /**
     * Remediation display setter
     *
     * @param bool $value
     * @return void
     */
    public function setRemediationDisplay(bool $value): void
    {
        $this->remediationDisplay = $value;
    }

    /**
     * Remediation display getter
     *
     * @return bool
     */
    public function hasRemediationDisplay(): bool
    {
        return $this->remediationDisplay;
    }

    /**
     * Get the bouncer instance
     *
     * @param array $settings
     * @param bool $forceReload
     * @return BouncerInstance
     */
    public function getBouncerInstance(array $settings = [], bool $forceReload = false): BouncerInstance
    {
        if ($this->bouncerInstance === null || $forceReload) {
            $this->logger = $settings['logger'];
            $this->setDisplayErrors($settings['display_errors']);

            try {
                $cache = $this->cacheFactory->create();
                $cacheAdapter = $cache->getAdapter(
                    $settings['cache_system'],
                    $settings['memcached_dsn'],
                    $settings['redis_dsn'],
                    $settings['fs_cache_path'],
                    $settings['forced_cache_system']
                );
            } catch (Exception $e) {
                throw new CrowdSecException($e->getMessage());
            }

            try {
                /** @var BouncerInstance $bouncerInstance */
                $bouncerInstance =
                    $this->bouncerInstanceFactory->create(
                        ['cacheAdapter' => $cacheAdapter, 'logger' => $this->logger ]
                    );

                $bouncerInstance->configure([
                    // LAPI connection
                    'api_key' => $settings['api_key'],
                    'api_url' => $settings['api_url'],
                    'api_user_agent' => $settings['api_user_agent'],
                    'api_timeout' => $settings['api_timeout'],
                    // Debug
                    'debug_mode' => $settings['debug_mode'],
                    'log_directory_path' => $settings['log_directory_path'],
                    'forced_test_ip' => $settings['forced_test_ip'],
                    'display_errors' => $settings['display_errors'],
                    // Bouncer
                    'bouncing_level' => $settings['bouncing_level'],
                    'trust_ip_forward_array' => $settings['trust_ip_forward_array'],
                    'fallback_remediation' => $settings['fallback_remediation'],
                    'max_remediation_level' => $settings['max_remediation_level'],
                    // Cache settings
                    'stream_mode' => $settings['stream_mode'],
                    'cache_system' => $settings['cache_system'],
                    'fs_cache_path' => $settings['fs_cache_path'],
                    'redis_dsn' => $settings['redis_dsn'],
                    'memcached_dsn' => $settings['memcached_dsn'],
                    'clean_ip_cache_duration' => $settings['clean_ip_cache_duration'],
                    'bad_ip_cache_duration' => $settings['bad_ip_cache_duration'],
                    // Geolocation
                    'geolocation' => $settings['geolocation']
                ]);
            } catch (Exception $e) {
                throw new CrowdSecException($e->getMessage());
            }

            $this->bouncerInstance = $bouncerInstance;
        }

        return $this->bouncerInstance;
    }

    /**
     * Initialize the bouncer instance
     *
     * @param array $configs
     * @param array $forcedConfigs
     * @return BouncerInstance
     */
    public function init(array $configs, array $forcedConfigs = []): BouncerInstance
    {
        $this->settings = array_merge($configs, $forcedConfigs);
        $this->bouncer = $this->getBouncerInstance($this->settings);

        return $this->bouncer;
    }

    /**
     * Retrieve http header by its name
     *
     * @param string $name
     * @return string|null
     */
    public function getHttpRequestHeader(string $name): ?string
    {
        return $this->helper->getHttpRequestHeader($name);
    }

    /**
     * Get the current IP, even if it's the IP of a proxy
     *
     * @return string
     */
    public function getRemoteIp(): string
    {
        return $this->helper->getRemoteIp();
    }

    /**
     * Get the current HTTP method
     *
     * @return string
     */
    public function getHttpMethod(): string
    {
        return $this->helper->getHttpMethod();
    }

    /**
     * Retrieve captcha wall options
     *
     * @return array
     */
    public function getCaptchaWallOptions(): array
    {
        return $this->helper->getCaptchaWallConfigs();
    }

    /**
     * Retrieve ban wall options
     *
     * @return array
     */
    public function getBanWallOptions(): array
    {
        return $this->helper->getBanWallConfigs();
    }

    /**
     * Retrieve IP ranges to trust as proxies as an array of comparables ip bounds
     *
     * @return array [[string, string], ...]
     */
    public function getTrustForwardedIpBoundsList(): array
    {
        return $this->helper->getTrustedForwardedIps();
    }

    /**
     * Get the value of a posted field.
     *
     * @param string $name
     * @return string|null
     */
    public function getPostedVariable(string $name): ?string
    {
        return $this->helper->getPostedVariable($name);
    }

    /**
     * If the current IP should be bounced or not, matching custom business rules.
     */
    public function shouldBounceCurrentIp(): bool
    {
        return true;
    }

    /**
     * Send HTTP response.
     *
     * @param string|null $body
     * @param int $statusCode
     * @return void
     */
    public function sendResponse(?string $body, int $statusCode = 200): void
    {
        $noCacheControl = 'no-store, no-cache, must-revalidate, max-age=0,post-check=0, pre-check=0';

        switch ($statusCode) {
            case 200:
                $code = Http::STATUS_CODE_200;
                break;
            case 401:
                $code = Http::STATUS_CODE_401;
                $this->response->setNoCacheHeaders();
                $this->response->setHeader('cache-control', $noCacheControl);
                break;
            case 403:
                $code = Http::STATUS_CODE_403;
                $this->response->setNoCacheHeaders();
                $this->response->setHeader('cache-control', $noCacheControl);
                break;
            default:
                throw new CrowdSecException("Unhandled code $statusCode");
        }
        if (null !== $body) {
            $this->setRemediationDisplay(true);
            $this->response->clearBody()
                ->setBody($body)
                ->setStatusCode($code);
        }
    }

    /**
     * If there is any technical problem while bouncing, don't block the user.
     *
     * Bypass bouncing and log the error.
     *
     * @param array $configs
     * @return bool
     * @throws InvalidArgumentException
     */
    public function safelyBounce(array $configs): bool
    {
        $result = false;
        try {
            $this->init($configs);
            $this->run();
            $result = true;
        } catch (CrowdSecException $e) {
            $this->logger->error('', [
                'type' => 'M2_EXCEPTION_WHILE_BOUNCING',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            if ($this->displayErrors) {
                throw $e;
            }
        }

        return $result;
    }
}
