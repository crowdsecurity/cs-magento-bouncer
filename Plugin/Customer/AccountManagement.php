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

/**
 * Plugin to handle log before authenticate
 */
class AccountManagement extends Event implements EventInterface
{

    /**
     * @var string
     */
    protected $type = 'CUSTOMER_LOGIN_PROCESS';

    protected $process = 'customer_login';

    public function getEventData($objects = []): array
    {
        return [];
    }

    /**
     * Add CrowdSec event log before authenticate method
     *
     * @param \Magento\Customer\Model\AccountManagement $subject
     * @noinspection PhpUnusedParameterInspection
     * @noinspection PhpMissingParamTypeInspection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAuthenticate($subject)
    {
        if ($this->helper->isEventsLogEnabled($this->process)) {
            $baseData = $this->getBaseData();
            $eventData = $this->getEventData();
            $finalData = array_merge($baseData, $eventData);
            if ($this->validateEvent($finalData)) {
                $this->helper->getEventLogger()->info('', $finalData);
            }
        }
    }
}
