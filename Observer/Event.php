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

use CrowdSec\Bouncer\Helper\Event as Helper;

class Event
{
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var string
     */
    protected $type = 'M2_EVENT';

    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }

    public function getSensitiveData(): array
    {
        return ["customer_email"];
    }

    public function getBaseData(): array
    {
        return [
            'type' => $this->type,
            'ip' => $this->helper->getRemoteIp(),
            'x-forwarder-for-ip' => $this->helper->getForwarderForIp()
        ];
    }

    /**
     * @param array $data
     * @param array $sensitiveData
     * @return array
     */
    public function hideSensitiveData(array $data, array $sensitiveData): array
    {
        $finalData = $data;
        if ($this->helper->shouldHideSensitive()) {
            foreach ($finalData as $key => $value) {
                if (in_array($key, $sensitiveData)) {
                    $finalData[$key] = hash('sha256', (string) $value);
                }
            }
        }

        return $finalData;
    }
}
