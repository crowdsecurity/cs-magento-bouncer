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

use CrowdSecBouncer\BouncerException;
use Exception;
use LogicException;
use Magento\Framework\App\Response\Http;
use CrowdSec\Bouncer\Helper\Data as Helper;
use CrowdSecBouncer\AbstractBounce;
use CrowdSecBouncer\Bouncer as BouncerInstance;
use CrowdSecBouncer\BouncerFactory;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;

class Bounce extends AbstractBounce
{

    /**
     * @var Http
     */
    protected $response;

    /**
     * @var Helper
     */
    protected $helper;

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
     * @param BouncerFactory $bouncerInstanceFactory
     */
    public function __construct(
        Http $response,
        Helper $helper,
        BouncerFactory $bouncerInstanceFactory
    ) {
        $this->response = $response;
        $this->helper = $helper;
        $this->bouncerInstanceFactory = $bouncerInstanceFactory;
    }

    /**
     * Init the logger.
     *
     * @param array $configs
     * @return void
     * @throws LogicException
     */
    public function initLogger(array $configs): void
    {
        $this->logger = $this->helper->getFinalLogger($configs);
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
     * @return BouncerInstance
     * @throws LogicException|BouncerException
     */
    public function getBouncerInstance(array $settings = []): BouncerInstance
    {
        if ($this->bouncerInstance === null) {
            $this->logger = $this->helper->getFinalLogger();

            try {
                /** @var BouncerInstance $bouncerInstance */
                $bouncerInstance =
                    $this->bouncerInstanceFactory->create(
                        ['configs' => $settings, 'logger' => $this->logger ]
                    );

            } catch (Exception $e) {
                throw new BouncerException($e->getMessage());
            }

            $this->bouncerInstance = $bouncerInstance;
        }

        return $this->bouncerInstance;
    }

    /**
     * Initialize the bouncer instance
     *
     * @param array $configs
     * @return BouncerInstance
     * @throws LogicException|BouncerException
     */
    public function init(array $configs): BouncerInstance
    {
        $this->settings = $configs;
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
     * @throws \InvalidArgumentException
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
     * @throws \Laminas\Http\Exception\InvalidArgumentException|BouncerException
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
                throw new BouncerException("Unhandled code $statusCode");
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
     * @throws BouncerException
     * @throws CacheException|LogicException
     */
    public function safelyBounce(array $configs): bool
    {
        $result = false;
        try {
            $this->init($configs);
            $this->run();
            $result = true;
        } catch (Exception $e) {
            $this->logger->error('', [
                'type' => 'M2_EXCEPTION_WHILE_BOUNCING',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            if (!empty($configs['display_errors'])) {
                throw $e;
            }
        }

        return $result;
    }
}
