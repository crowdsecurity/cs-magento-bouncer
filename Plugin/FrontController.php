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

namespace CrowdSec\Bouncer\Plugin;

use Closure;
use CrowdSecBouncer\BouncerException;
use Throwable;
use LogicException;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\State;
use CrowdSec\Bouncer\Helper\Data as HelperData;
use CrowdSec\Bouncer\Registry\CurrentBouncer as RegistryBouncer;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Psr\Cache\CacheException;

/**
 * Plugin to handle controller request before Full Page Cache
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class FrontController
{

    /**
     * @var ActionFlag
     */
    protected $actionFlag;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var RegistryBouncer
     */
    protected $registryBouncer;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * constructor.
     * @param HelperData $helper
     * @param ActionFlag $actionFlag
     * @param State $state
     * @param RegistryBouncer $registryBouncer
     * @param ResponseInterface $response
     *
     */
    public function __construct(
        HelperData $helper,
        ActionFlag $actionFlag,
        State $state,
        RegistryBouncer $registryBouncer,
        ResponseInterface $response
    ) {
        $this->helper = $helper;
        $this->actionFlag = $actionFlag;
        $this->state = $state;
        $this->registryBouncer = $registryBouncer;
        $this->response = $response;
    }

    /**
     * Add CrowdSec functionality to Dispatch method
     *
     * @param FrontControllerInterface $subject
     * @param Closure $proceed
     * @param RequestInterface $request
     * @return ResponseInterface|mixed
     * @throws LocalizedException
     * @throws LogicException
     * @throws BouncerException
     * @throws CacheException
     */
    public function aroundDispatch(
        FrontControllerInterface $subject,
        Closure $proceed,
        RequestInterface $request
    ) {
        // Check if we are in prepend mode
        if (defined('CROWDSEC_PREPEND_RUNNING_CONTEXT')) {
            return $proceed($request);
        }
        // Check if feature is enabled
        if (!$this->helper->isEnabled($this->state->getAreaCode())) {
            return $proceed($request);
        }
        /**
         * If there is any technical problem while bouncing, don't block the user.
         * Bypass bouncing and log the  error.
         *
         */
        try {
            return $this->bounce($subject, $proceed, $request);
        } catch (Throwable $e) {
            $this->helper->critical('Error while bouncing', [
                'type' => 'M2_EXCEPTION_WHILE_BOUNCING',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if ($this->helper->canDisplayErrors()) {
                throw $e;
            }

            return $proceed($request);
        }
    }

    /**
     * Bounce process
     *
     * @param FrontControllerInterface $subject
     * @param Closure $proceed
     * @param RequestInterface $request
     * @return ResponseInterface|mixed
     * @throws LogicException
     * @throws BouncerException
     * @throws FileSystemException
     * @throws CacheException
     * @noinspection PhpUnusedParameterInspection
     */
    private function bounce(
        FrontControllerInterface $subject,
        Closure $proceed,
        RequestInterface $request
    ) {
        // Avoid multiple call
        if ($this->registryBouncer->get()) {
            return $proceed($request);
        }
        $configs = $this->helper->getBouncerConfigs();
        $registryBouncer = $this->registryBouncer->create(
            [
                'helper' => $this->helper,
                'configs' => $configs,
                'response' => $this->response
            ]
        );

        $registryBouncer->run();

        // If ban or captcha remediation wall display is detected
        if ($registryBouncer->hasRemediationDisplay()) {
            // Stop further processing if your condition is met
            $this->actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);

            return $this->response;
        }

        return $proceed($request);
    }
}
