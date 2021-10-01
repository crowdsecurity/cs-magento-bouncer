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

class Order extends Event implements EventInterface, ObserverInterface
{
    /**
     * @var string
     */
    protected $type = 'M2_EVENT_ORDER';

    public function getOptionalData($objects): array
    {
        return [];
    }

    public function getAdditionalData($objects): array
    {
        $order = $objects['order'] ?? null;

        return $order ?
            [
                'order_increment_id' => $order->getIncrementId(),
                'customer_id' => $order->getCustomerId(),
                'customer_is_guest' => $order->getCustomerIsGuest(),
                'quote_id' => $order->getQuoteId(),
                'applied_rule_ids' => $order->getAppliedRuleIds(),
                'grand_total' => $order->getGrandTotal()
            ] : [];
    }

    public function execute(Observer $observer)
    {
        if ($this->helper->isEventsLogEnabled()) {
            $order = $observer->getOrder();
            $baseData = $this->getBaseData();
            $dataObjects = ['order' => $order];
            $additionalData = $this->getAdditionalData($dataObjects);
            $optionalData = $this->getOptionalData($dataObjects);
            $this->helper->getEventLogger()->info('', array_merge($baseData, $additionalData, $optionalData));
        }

        return $this;
    }
}
