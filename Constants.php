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

namespace CrowdSec\Bouncer;

use CrowdSecBouncer\Constants as LibConstants;

class Constants extends LibConstants
{

    /** @var string The last version of this library */
    public const VERSION = 'v1.1.0';

    /** @var string The user agent used to send request to LAPI or CAPI */
    public const BASE_USER_AGENT = 'Magento 2 CrowdSec Bouncer/'.self::VERSION;

    /** @var string  */
    public const CROWDSEC_CACHE_PATH = BP . '/var/cache/crowdsec';

    /** @var string  */
    public const CROWDSEC_LOG_PATH = '/var/log';
}
