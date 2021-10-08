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

class Quote extends Event implements EventInterface, ObserverInterface
{
    /**
     * @var string
     */
    protected $type = 'M2_EVENT_QUOTE';

    public function getEventData($objects): array
    {
        $product = $objects['product'] ?? null;
        $quoteItem = $objects['quote_item'] ?? null;
        $productData = $product ? ['product_id' => $product->getId()] : [];
        $quoteItemData = $quoteItem ? ['quote_id' => (string) $quoteItem->getQuoteId()] : [];

        return array_merge($productData, $quoteItemData);
    }

    public function execute(Observer $observer)
    {
        if ($this->helper->isEventsLogEnabled()) {
            $product = $observer->getProduct();
            $quoteItem = $observer->getQuoteItem();
            $baseData = $this->getBaseData();
            $dataObjects = ['product' => $product, 'quote_item' => $quoteItem];
            $eventData = $this->getEventData($dataObjects);
            $finalData = $this->hideSensitiveData(array_merge($baseData, $eventData), $this->getSensitiveData());
            $this->helper->getEventLogger()->info('', $finalData);
        }

        return $this;
    }
}
