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

namespace CrowdSec\Bouncer\Helper;

use Magento\Framework\App\Helper\Context;
use CrowdSec\Bouncer\Logger\Logger;
use CrowdSec\Bouncer\Logger\EventLogger;
use CrowdSec\Bouncer\Logger\Handlers\DebugFactory as DebugHandler;
use Magento\Framework\Serialize\Serializer\Json;

class Event extends Data
{
    /**
     * Event logger
     * @var EventLogger
     */
    protected $_eventLogger;

    protected $_optionalData = [];

    /**
     * Data constructor.
     * @param EventLogger $eventLogger
     * @param Logger $logger
     * @param DebugHandler $debugHandler
     * @param Context $context
     * @param Json $serializer
     */
    public function __construct(
        EventLogger $eventLogger,
        Logger       $logger,
        DebugHandler $debugHandler,
        Context      $context,
        Json         $serializer
    ) {
        parent::__construct($logger, $debugHandler, $context, $serializer);
        $this->_eventLogger = $eventLogger;
    }

    /**
     * Event Loger getter
     * @return EventLogger
     */
    public function getEventLogger(): EventLogger
    {
        return $this->_eventLogger;
    }
}
