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

use CrowdSec\Bouncer\Helper\Data as Helper;
use Magento\Framework\Data\OptionSourceInterface;

class Cache implements OptionSourceInterface
{

    /**
     * @var Helper
     */
    private $helper;

    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $result = [];
        foreach ($this->helper->getCacheSystemOptions() as $value => $label) {
            $result[] = ['value' => $value, 'label' => $label];

        }
        return $result;
    }
}
