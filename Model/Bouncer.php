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

/**
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Bouncer extends AbstractBounce implements IBounce
{

    /**
     * @var Http
     */
    protected $response;

    /**
     * @var Session
     */
    protected $session;

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

    /** bool */
    protected $remediationDisplay = false;

    /** @var array|null */
    protected $bouncerConfigs;

    public function __construct(
        Http $response,
        Session $session,
        Helper $helper,
        CacheFactory $cacheFactory,
        BouncerFactory $bouncerInstanceFactory
    ) {
        $this->response = $response;
        $this->session = $session;
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

    public function setRemediationDisplay(bool $value): void
    {
        $this->remediationDisplay = $value;
    }

    public function getRemediationDisplay(): bool
    {
        return $this->remediationDisplay;
    }

    /**
     * Get the bouncer instance
     * @param array $forcedConfigs
     * @return BouncerInstance
     * @throws CrowdSecException
     */
    public function getBouncerInstance(array $forcedConfigs = []): BouncerInstance
    {
        if ($this->bouncerInstance === null || !empty($forcedConfigs)) {
            $configs = $this->helper->getBouncerConfigs();
            // We replace configs by forced configs
            if (is_array($forcedConfigs)) {
                $configs = array_merge($configs, $forcedConfigs);
            }
            $this->bouncerConfigs = $configs;
            $this->logger = $configs['logger'];
            $this->setDisplayErrors($configs['display_errors']);

            try {
                $cache = $this->cacheFactory->create();
                $cacheAdapter = $cache->getAdapter(
                    $configs['cache_system'],
                    $configs['memcached_dsn'],
                    $configs['redis_dsn'],
                    $configs['fs_cache_path'],
                    $configs['forced_cache_system']
                );
            } catch (Exception $e) {
                throw new CrowdSecException(__($e->getMessage()));
            }

            try {
                $bouncerInstance =
                    $this->bouncerInstanceFactory->create(
                        ['cacheAdapter' => $cacheAdapter, 'logger' => $this->logger ]
                    );
                $bouncerInstance->configure([
                    'api_key' => $configs['api_key'],
                    'api_url' => $configs['api_url'],
                    'api_user_agent' => $configs['api_user_agent'],
                    'live_mode' => $configs['live_mode'],
                    'max_remediation_level' => $configs['max_remediation_level'],
                    'fallback_remediation' => $configs['fallback_remediation'],
                    'cache_expiration_for_clean_ip' => $configs['clean_ip_duration'],
                    'cache_expiration_for_bad_ip' => $configs['bad_ip_duration'],
                ]);
            } catch (Exception $e) {
                throw new CrowdSecException(__($e->getMessage()));
            }

            $this->bouncerInstance = $bouncerInstance;
        }

        return $this->bouncerInstance;
    }

    /**
     * Initialize the bouncer instance
     * @param array $forcedConfigs
     * @return BouncerInstance
     * @throws CrowdSecException
     */
    public function init(array $forcedConfigs = []): BouncerInstance
    {
        $this->bouncer = $this->getBouncerInstance($forcedConfigs);

        return $this->bouncer;
    }

    /**
     * @return string Ex: "X-Forwarded-For"
     */
    public function getHttpRequestHeader(string $name): ?string
    {
        return $this->helper->getHttpRequestHeader($name);
    }

    /**
     * @return string The current IP, even if it's the IP of a proxy
     */
    public function getRemoteIp(): string
    {
        return $this->helper->getRemoteIp();
    }

    /**
     * @return string The current HTTP method
     */
    public function getHttpMethod(): string
    {
        return $this->helper->getHttpMethod();
    }

    /**
     * @return array ['hide_crowdsec_mentions': bool, color:[text:['primary' : string, 'secondary' : string, 'button' :
     *     string, 'error_message : string' ...]]] (returns an array of option required to build the captcha wall
     *     template)
     */
    public function getCaptchaWallOptions(): array
    {
        return $this->helper->getCaptchaWallConfigs();
    }

    /**
     * @return array ['hide_crowdsec_mentions': bool, color:[text:['primary' : string, 'secondary' : string,
     *     'error_message : string' ...]]] (returns an array of option required to build the ban wall template)
     */
    public function getBanWallOptions(): array
    {
        return $this->helper->getBanWallConfigs();
    }

    /**
     * @return array [[string, string], ...] Returns IP ranges to trust as proxies as an array of comparables ip bounds
     */
    public function getTrustForwardedIpBoundsList(): array
    {
        return $this->helper->getTrustedForwardedIps();
    }

    /**
     * Return a session variable, null if not set.
     */
    public function getSessionVariable(string $name)
    {
        return $this->session->getData($name);
    }

    public function getAllSessionVariables()
    {
        return $this->session->getData();
    }

    /**
     * Set a session variable.
     */
    public function setSessionVariable(string $name, $value): void
    {
        $this->session->setData($name, $value);
    }

    /**
     * Unset a session variable, throw an error if this does not exist.
     *
     * @return void;
     */
    public function unsetSessionVariable(string $name): void
    {
        $this->session->unsetData($name);
    }

    /**
     * Get the value of a posted field.
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
        $shouldBounce = $this->isConfigValid();
        if (!$shouldBounce) {
            $this->logger->warning('', [
                'type' => 'M2_INVALID_BOUNCER_CONFIG',
                'message' => 'No bouncing because bouncer config is not valid.',
            ]);
        }

        return $shouldBounce;
    }

    /**
     * Send HTTP response.
     * @throws CrowdSecException
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
                throw new CrowdSecException(__("Unhandled code $statusCode"));
        }
        if (null !== $body) {
            $this->setRemediationDisplay(true);
            $this->response->clearBody()
                ->setBody($body)
                ->setStatusCode($code);
        }
    }

    /**
     * Check if the bouncer configuration is correct or not.
     */
    public function isConfigValid(): bool
    {
        $result = false;
        $configs = $this->bouncerConfigs;
        if (is_array($configs) && !empty($configs['api_url']) && !empty($configs['api_key'])) {
            $result = true;
        }

        return $result;
    }
}
