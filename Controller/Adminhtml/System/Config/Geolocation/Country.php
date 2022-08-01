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

namespace CrowdSec\Bouncer\Controller\Adminhtml\System\Config\Geolocation;

use CrowdSec\Bouncer\Controller\Adminhtml\System\Config\Action;
use CrowdSec\Bouncer\Registry\CurrentBounce as RegistryBounce;
use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use CrowdSec\Bouncer\Helper\Data as Helper;
use CrowdSec\Bouncer\Constants;
use CrowdSecBouncer\Geolocation;
use Magento\Framework\App\Filesystem\DirectoryList;

class Country extends Action implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Helper
     */
    protected $helper;

    /** @var  Geolocation */
    protected $geolocation;

    /** @var  DirectoryList */
    protected $directoryList;

    /**
     * @var RegistryBounce
     */
    protected $registryBounce;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Helper $helper
     * @param Geolocation $geolocation
     * @param DirectoryList $directoryList
     * @param RegistryBounce $registryBounce
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Helper $helper,
        Geolocation $geolocation,
        DirectoryList $directoryList,
        RegistryBounce $registryBounce
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->geolocation = $geolocation;
        $this->directoryList = $directoryList;
        $this->registryBounce = $registryBounce;
    }

    /**
     * Test geolocation
     *
     * @return Json
     */
    public function execute(): Json
    {
        try {
            if (!($bounce = $this->registryBounce->get())) {
                $bounce = $this->registryBounce->create();
            }
            $configs = $this->helper->getBouncerConfigs();
            $bouncer = $bounce->init($configs);
            $apiCache = $bouncer->getApiCache();

            $type = $this->getRequest()->getParam('geolocation_type');
            $maxmindType = $this->getRequest()->getParam('geolocation_maxmind_database_type');
            $maxmindPath = $this->getRequest()->getParam('geolocation_maxmind_database_path');
            $forcedIP = $this->getRequest()->getParam('forced_test_ip');
            $saveResult = (bool) $this->getRequest()->getParam('save_result');
            $ip = !empty($forcedIP) ? $forcedIP : $this->helper->getRemoteIp();
            $geolocConfig = [
                'type' => $type,
                'save_result' => $saveResult,
            ];
            if ($type === Constants::GEOLOCATION_TYPE_MAXMIND) {
                $geolocConfig['maxmind'] = [
                    'database_type' => $maxmindType,
                    'database_path' => $this->helper->getGeolocationDatabaseFullPath($maxmindPath)
                ];
            }

            $countryResult = $this->geolocation->getCountryResult($geolocConfig, $ip, $apiCache);
            $countryMessage = null;
            $result = false;
            $message = __('Geolocation test result: failed.');
            if (!empty($countryResult['country'])) {
                $countryMessage = $countryResult['country'];
                $result = 1;
                $message = __('Geolocation test result: success.');
            } elseif (!empty($countryResult['not_found'])) {
                $countryMessage = $countryResult['not_found'];
            } elseif (!empty($countryResult['error'])) {
                $countryMessage = $countryResult['error'];
            }
        } catch (Exception $e) {
            $result = false;
            $message = __('Technical error while testing geolocation: ' . $e->getMessage());
        }

        $resultJson = $this->resultJsonFactory->create();

        if (!empty($forcedIP)) {
            $finalMessage = $message .'<br><br>'. __(
                'Tested IP (forced test IP): %1 <br> Country result: %2',
                $ip??"",
                $countryMessage??""
            );

        } else {
            $finalMessage = $message .'<br><br>'. __(
                'Tested IP (remote IP): %1 <br> Country result: %2',
                $ip??"",
                $countryMessage??""
            );
        }

        return $resultJson->setData([
            'test' => $result,
            'message' => $finalMessage,
        ]);
    }
}
