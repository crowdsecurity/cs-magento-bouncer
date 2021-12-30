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
use ErrorException;
use Exception;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\State;
use CrowdSec\Bouncer\Helper\Data as HelperData;
use CrowdSec\Bouncer\Registry\CurrentBouncer as RegistryBouncer;
use Magento\Framework\Exception\LocalizedException;

/**
 * Plugin to handle controller request before Full Page Cache
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
     * @throws ErrorException
     * @throws LocalizedException
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

        try {
            // If there is any technical problem while bouncing, don't block the user. Bypass bouncing and log the
            // error.
            set_error_handler(function ($errno, $errstr, $errfile, $errline) {
                throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
            });

            $result = $this->bounce($subject, $proceed, $request);

            restore_error_handler();

            return $result;
        } catch (Exception $e) {
            $this->helper->critical('', [
                'type' => 'M2_EXCEPTION_WHILE_BOUNCING',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if ($this->helper->canDisplayErrors()) {
                throw $e;
            }

            restore_error_handler();

            return $proceed($request);
        }
    }

    /**
     * @param FrontControllerInterface $subject
     * @param Closure $proceed
     * @param RequestInterface $request
     * @return ResponseInterface|mixed
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
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
        $registryBouncer = $this->registryBouncer->create();
        $registryBouncer->init();
        $registryBouncer->run();

        // If ban or captcha remediation wall display is detected
        if ($registryBouncer->getRemediationDisplay()) {
            // Stop further processing if your condition is met
            $this->actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);

            return $this->response;
        }

        return $proceed($request);
    }
}
