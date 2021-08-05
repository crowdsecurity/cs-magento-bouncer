<?php declare(strict_types=1);
/**
 * Crowdsec_Bouncer Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT LICENSE
 * that is bundled with this package in the file LICENSE
 *
 * @category   Crowdsec
 * @package    Crowdsec_Bouncer
 * @copyright  Copyright (c)  2021+ CrowdSec
 * @author     CrowdSec team
 * @see        https://crowdsec.net CrowdSec Official Website
 * @license    MIT LICENSE
 *
 */

/**
 *
 * @category Crowdsec
 * @package  Crowdsec_Bouncer
 * @module   Bouncer
 * @author   CrowdSec team
 *
 */
namespace Crowdsec\Bouncer\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action as BackendAction;

abstract class Action extends BackendAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Crowdsec_Bouncer::manage_config_actions';
}
