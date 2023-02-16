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
use CrowdSec\Bouncer\Registry\CurrentBouncer as RegistryBouncer;
use CrowdSec\RemediationEngine\GeolocationFactory as GeolocationFactory;
use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use CrowdSec\Bouncer\Helper\Data as Helper;
use CrowdSec\Bouncer\Constants;
use Magento\Framework\App\Filesystem\DirectoryList;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;

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

    /** @var  GeolocationFactory */
    protected $geolocation;

    /** @var  DirectoryList */
    protected $directoryList;

    /**
     * @var RegistryBouncer
     */
    protected $registryBouncer;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Helper $helper
     * @param GeolocationFactory $geolocationFactory
     * @param DirectoryList $directoryList
     * @param RegistryBouncer $registryBouncer
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Helper $helper,
        GeolocationFactory $geolocationFactory,
        DirectoryList $directoryList,
        RegistryBouncer $registryBouncer
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->geolocation = $geolocationFactory;
        $this->directoryList = $directoryList;
        $this->registryBouncer = $registryBouncer;
    }

    /**
     * Test geolocation
     *
     * @return Json
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    public function execute(): Json
    {
        try {
            $configs = $this->helper->getBouncerConfigs();
            $bouncer = $this->registryBouncer->create([
                'helper' => $this->helper,
                'configs' => $configs
            ]);

            $cacheStorage = $bouncer->getRemediationEngine()->getCacheStorage();

            $type = $this->getRequest()->getParam('geolocation_type');
            $maxmindType = $this->getRequest()->getParam('geolocation_maxmind_database_type');
            $maxmindPath = $this->getRequest()->getParam('geolocation_maxmind_database_path');
            $forcedIP = $this->getRequest()->getParam('forced_test_ip');
            $cacheDuration = (int) $this->getRequest()->getParam('cache_duration');
            $ip = !empty($forcedIP) ? $forcedIP : $this->helper->getRemoteIp();
            $geolocConfig = [
                'type' => $type,
                'cache_duration' => $cacheDuration,
            ];
            if ($type === Constants::GEOLOCATION_TYPE_MAXMIND) {
                $geolocConfig['maxmind'] = [
                    'database_type' => $maxmindType,
                    'database_path' => $this->helper->getVarFullPath($maxmindPath)
                ];
            }
            $logger = $bouncer->getLogger();

            $geolocation = $this->geolocation->create(
                ['configs' => $geolocConfig, 'cacheStorage' => $cacheStorage, 'logger' => $logger]
            );

            $countryResult = $geolocation->handleCountryResultForIp($ip);
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
