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

namespace CrowdSec\Bouncer\Controller\Adminhtml\System\Config\Connection;

use CrowdSec\Bouncer\Controller\Adminhtml\System\Config\Action;
use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use CrowdSec\Bouncer\Registry\CurrentBounce as RegistryBounce;
use CrowdSec\Bouncer\Helper\Data as Helper;
use CrowdSec\Bouncer\Constants;

class Ping extends Action implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var RegistryBounce
     */
    protected $registryBounce;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param RegistryBounce $registryBounce
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        RegistryBounce $registryBounce,
        Helper $helper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->registryBounce = $registryBounce;
        $this->helper = $helper;
    }

    /**
     * Test connection
     *
     * @return Json
     */
    public function execute(): Json
    {
        try {
            $baseUri = $this->getRequest()->getParam('api_url');
            $userAgent = Constants::BASE_USER_AGENT;
            $apiKey = $this->getRequest()->getParam('bouncer_key');
            $configs = $this->helper->getBouncerConfigs();
            $currentConfigs = ['api_url' => $baseUri, 'api_user_agent' => $userAgent, 'api_key' => $apiKey];
            $finalConfigs = array_merge($configs, $currentConfigs);
            /** @var \CrowdSec\Bouncer\Model\Bounce $bounce */
            $bounce = $this->registryBounce->create();
            $bouncer = $bounce->init($finalConfigs);
            $restClient = $bouncer->getRestClient();
            $this->helper->ping($restClient);
            $result = 1;
            $message = __('Connection test result: success.');
        } catch (Exception $e) {
            $this->helper->error('', [
                'type' => 'M2_EXCEPTION_WHILE_TESTING_CONNECTION',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $result = false;
            $message = __('Technical error while testing connection: ' . $e->getMessage());
        }

        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData([
            'connection' => $result,
            'message' => $message .'<br><br>'. __('Tested url: %1 <br> Tested key: %2', $baseUri??"", $apiKey??""),
        ]);
    }
}
