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

namespace CrowdSec\Bouncer\Plugin\Checkout;

use CrowdSec\Bouncer\Event\Event;
use CrowdSec\Bouncer\Event\EventInterface;
use Magento\Checkout\Controller\Cart\Add;

/**
 * Plugin to handle log before adding product to cart
 */
class CartAddController extends Event implements EventInterface
{

    /**
     * @var string
     */
    protected $type = 'ADD_TO_CART_PROCESS';

    public function getEventData($objects = []): array
    {
        $controller = $objects['controller'] ?? null;
        return $controller ? ['product_id' => (string)$controller->getRequest()->getParam('product')] : [];
    }

    /**
     * Add CrowdSec event log before adding product to cart
     *
     * @param Add $subject
     * @noinspection PhpMissingParamTypeInspection
     */
    public function beforeExecute($subject)
    {
        if ($this->helper->isEventsLogEnabled()) {
            $baseData = $this->getBaseData();
            $eventData = $this->getEventData(['controller' => $subject]);
            $finalData = array_merge($baseData, $eventData);
            if ($this->validateEvent($finalData)) {
                $this->helper->getEventLogger()->info('', $finalData);
            }
        }
    }
}
