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
namespace CrowdSec\Bouncer\Model\Config\Source;

use CrowdSec\Bouncer\Constants;
use CrowdSec\RemediationEngine\LapiRemediation;
use Magento\Framework\Data\OptionSourceInterface;

class Fallback implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $result = [];
        $orderedRemediations = array_merge(LapiRemediation::ORDERED_REMEDIATIONS, [Constants::REMEDIATION_BYPASS]);
        foreach ($orderedRemediations as $remediation) {
            $result[] = ['value' => $remediation, 'label' => __("$remediation")];
        }

        return $result;
    }
}
