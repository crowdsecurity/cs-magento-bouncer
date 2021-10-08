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

interface EventInterface
{

    /**
     * Common data for all events
     * @return array
     */
    public function getBaseData(): array;

    /**
     * Data for a specific event
     * @param array $objects
     * @return array
     */
    public function getEventData(array $objects): array;

    /**
     * Sensitive data
     * @return array
     */
    public function getSensitiveData(): array;
}
