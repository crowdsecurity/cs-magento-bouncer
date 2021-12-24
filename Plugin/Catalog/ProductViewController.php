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

namespace CrowdSec\Bouncer\Plugin\Catalog;

use CrowdSec\Bouncer\Event\Event;
use CrowdSec\Bouncer\Event\EventInterface;
use Magento\Catalog\Controller\Product\View;

/**
 * Plugin to handle log before viewing uncached product page
 */
class ProductViewController extends Event implements EventInterface
{

    /**
     * @var string
     */
    protected $type = 'PRODUCT_VIEW';

    public function getEventData($objects = []): array
    {
        $controller = $objects['controller'] ?? null;
        return $controller ? ['product_id' => (string)$controller->getRequest()->getParam('id')] : [];
    }

    /**
     * Add CrowdSec event log before viewing uncached product page
     *
     * @param View $subject
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
