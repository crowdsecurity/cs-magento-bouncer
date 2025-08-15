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

use CrowdSec\RemediationEngine\CacheStorage\CacheStorageException;
use CrowdSecBouncer\BouncerException;
use CrowdSec\RemediationEngine\LapiRemediation;
use Laminas\Http\Exception\InvalidArgumentException;
use Magento\Framework\App\Response\Http;
use CrowdSec\Bouncer\Helper\Data as Helper;
use CrowdSecBouncer\AbstractBouncer;
use CrowdSecBouncer\BouncerFactory;

class Bouncer extends AbstractBouncer
{

    /**
     * @var Helper
     */
    protected $helper;
    /** @var bool */
    protected $remediationDisplay = false;
    /**
     * @var null | Http
     */
    protected $response;

    /**
     * @param Helper $helper
     * @param array $configs
     * @param Http|null $response
     * @throws BouncerException
     * @throws CacheStorageException
     * @throws \LogicException
     */
    public function __construct(Helper $helper, array $configs, ?Http $response = null)
    {
        $this->helper = $helper;
        $this->response = $response;
        $this->logger = $this->helper->getFinalLogger();
        $client = $this->handleClient($configs, $this->logger);
        $cache = $this->handleCache($configs, $this->logger);
        $remediation = new LapiRemediation($configs, $client, $cache, $this->logger);

        parent::__construct($configs, $remediation, $this->logger);
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
     * Get the current IP, even if it's the IP of a proxy
     *
     * @return string
     */
    public function getRemoteIp(): string
    {
        return $this->helper->getRemoteIp();
    }

    /**
     * Fake implementation as we don't use AppSec
     *
     * @return array
     */
    public function getRequestHeaders():array
    {
        return [];
    }

    /**
     * Fake implementation as we don't use AppSec
     *
     * @return string
     */
    public function getRequestHost(): string
    {
        return '';
    }

    /**
     * Fake implementation as we don't use AppSec
     *
     * @return string
     */
    public function getRequestRawBody(): string
    {
        return '';
    }

    /**
     * Get the URI for the current request as a string
     *
     * @return string
     */
    public function getRequestUri(): string
    {
        return $this->helper->getRequestUri();
    }

    /**
     * Fake implementation as we don't use AppSec
     *
     * @return string
     */
    public function getRequestUserAgent(): string
    {
        return '';
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
     * If the current IP should be bounced or not, matching custom business rules.
     */
    public function shouldBounceCurrentIp(): bool
    {
        return true;
    }

    /**
     * Send HTTP response.
     *
     * @param string $body
     * @param int $statusCode
     * @return void
     * @throws BouncerException
     * @throws InvalidArgumentException
     */
    protected function sendResponse(string $body, int $statusCode): void
    {
        if ($this->response) {
            $noCacheControl = 'no-store, no-cache, must-revalidate, max-age=0,post-check=0, pre-check=0';

            switch ($statusCode) {
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
            $this->setRemediationDisplay(true);
            $this->response->clearBody()
                ->setBody($body)
                ->setStatusCode($code);
        }
    }
}
