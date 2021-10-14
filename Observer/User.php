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

namespace CrowdSec\Bouncer\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class User extends Event implements EventInterface, ObserverInterface
{
    /**
     * @var string
     */
    protected $type = 'M2_EVENT_USER';

    public function getEventData($objects): array
    {
        $userName = $objects['user_name'] ?? null;
        $userData =  $userName ? ['user_name' => $userName] : [];
        $exception = $objects['exception'] ?? null;
        $exceptionData =  $exception ? ['exception_message' => $exception->getMessage()] : [];

        return array_merge($userData, $exceptionData);
    }

    public function execute(Observer $observer)
    {
        if ($this->helper->isEventsLogEnabled()) {
            $userName = $observer->getUserName();
            $exception = $observer->getException();
            $baseData = $this->getBaseData();
            $dataObjects = ['user_name' => $userName, 'exception' => $exception];
            $eventData = $this->getEventData($dataObjects);
            $finalData = $this->hideSensitiveData(array_merge($baseData, $eventData), $this->getSensitiveData());
            $this->helper->getEventLogger()->info('', $finalData);
        }

        return $this;
    }
}
