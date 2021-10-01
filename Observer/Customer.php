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

class Customer extends Event implements EventInterface, ObserverInterface
{
    /**
     * @var string
     */
    protected $type = 'M2_EVENT_CUSTOMER';

    public function getOptionalData($objects): array
    {
        $customer = $objects['customer'] ?? null;

        return $customer ? ['customer_email' => $this->helper->isOptionalLogEnabled('customer_email') ?
            $customer->getEmail() :
            self::UNAUTHORIZED] : [];
    }

    public function getAdditionalData($objects): array
    {
        $customer = $objects['customer'] ?? null;
        return $customer ? ['customer_id' => $customer->getId()] : [];
    }

    public function execute(Observer $observer)
    {
        if ($this->helper->isEventsLogEnabled()) {
            $customer = $observer->getCustomer();
            $baseData = $this->getBaseData();
            $dataObjects = ['customer' => $customer];
            $additionalData = $this->getAdditionalData($dataObjects);
            $optionalData = $this->getOptionalData($dataObjects);
            $this->helper->getEventLogger()->info('', array_merge($baseData, $additionalData, $optionalData));
        }

        return $this;
    }
}
