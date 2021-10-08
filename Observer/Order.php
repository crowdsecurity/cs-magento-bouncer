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

    public function getEventData($objects): array
    {
        $order = $objects['order'] ?? null;

        return $order ?
            [
                'order_increment_id' => $order->getIncrementId(),
                'customer_id' => (string) $order->getCustomerId(),
                'customer_email' => $order->getCustomerEmail(),
                'customer_is_guest' => (string) $order->getCustomerIsGuest(),
                'quote_id' => $order->getQuoteId(),
                'applied_rule_ids' => $order->getAppliedRuleIds(),
                'grand_total' => (string) $order->getGrandTotal()
            ] : [];
    }

    public function execute(Observer $observer)
    {
        if ($this->helper->isEventsLogEnabled()) {
            $order = $observer->getOrder();
            $baseData = $this->getBaseData();
            $dataObjects = ['order' => $order];
            $eventData = $this->getEventData($dataObjects);
            $finalData = $this->hideSensitiveData(array_merge($baseData, $eventData), $this->getSensitiveData());
            $this->helper->getEventLogger()->info('', $finalData);
        }

        return $this;
    }
}
