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

namespace CrowdSec\Bouncer\Plugin\Customer;

use CrowdSec\Bouncer\Event\Event;
use CrowdSec\Bouncer\Event\EventInterface;
use Magento\Customer\Controller\Account\CreatePost;

/**
 * Plugin to handle log before customer registering
 */
class AccountCreatePostController extends Event implements EventInterface
{

    /**
     * @var string
     */
    protected $type = 'CUSTOMER_REGISTER_PROCESS';

    public function getEventData($objects = []): array
    {
        return [];
    }

    /**
     * Add CrowdSec event log before customer registering from post action
     *
     * @param CreatePost $subject
     * @noinspection PhpMissingParamTypeInspection
     */
    public function beforeExecute($subject)
    {
        if ($this->helper->isEventsLogEnabled()) {
            $baseData = $this->getBaseData();
            $eventData = $this->getEventData();
            $finalData = array_merge($baseData, $eventData);
            if ($this->validateEvent($finalData)) {
                $this->helper->getEventLogger()->info('', $finalData);
            }
        }
    }
}
