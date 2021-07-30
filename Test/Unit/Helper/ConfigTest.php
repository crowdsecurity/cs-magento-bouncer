<?php
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

namespace Crowdsec\Bouncer\Test\Unit\Helper;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Crowdsec\Bouncer\Helper\Config;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Config
     */
    protected $helper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $className = Config::class;
        $arguments = $objectManagerHelper->getConstructArguments($className);

        /** @var \Magento\Framework\App\Helper\Context $context */
        $context = $arguments['context'];
        $this->scopeConfig = $context->getScopeConfig();

        $this->helper = $objectManagerHelper->getObject($className, $arguments);
    }

    public function testIsFrontEnabled()
    {
        $scopeResult = true;
        $this->scopeConfig->expects($this->once())->method('getValue')->with(
            Config::XML_PATH_FRONT_ENABLED,
            ScopeInterface::SCOPE_STORE
        )
            ->willReturn($scopeResult);
        $result = $this->helper->isFrontEnabled();

        $this->assertEquals($result, $scopeResult);
    }

    public function testIsFrontDisabled()
    {
        $scopeResult = false;
        $this->scopeConfig->expects($this->once())->method('getValue')->with(
            Config::XML_PATH_FRONT_ENABLED,
            ScopeInterface::SCOPE_STORE
        )
            ->willReturn($scopeResult);
        $result = $this->helper->isFrontEnabled();

        $this->assertEquals($result, $scopeResult);
    }

    public function testIsAdminEnabled()
    {
        $scopeResult = true;
        $this->scopeConfig->expects($this->once())->method('getValue')->with(
            Config::XML_PATH_ADMIN_ENABLED
        )
            ->willReturn($scopeResult);
        $result = $this->helper->isAdminEnabled();

        $this->assertEquals($result, $scopeResult);
    }

    public function testIsAdminDisabled()
    {
        $scopeResult = false;
        $this->scopeConfig->expects($this->once())->method('getValue')->with(
            Config::XML_PATH_ADMIN_ENABLED
        )
            ->willReturn($scopeResult);
        $result = $this->helper->isAdminEnabled();

        $this->assertEquals($result, $scopeResult);
    }

    public function testIsDebugLog()
    {
        $scopeResult = true;

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    [
                        Config::XML_PATH_ADVANCED_DEBUG_LOG,
                        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        null,
                        $scopeResult,
                    ]

                ]
            );
        $result = $this->helper->isDebugLog();
        $this->assertEquals($result, $scopeResult);
    }

    public function testIsNotDebugLog()
    {
        $scopeResult = false;
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    [
                        Config::XML_PATH_ADVANCED_DEBUG_LOG,
                        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        null,
                        $scopeResult,
                    ]

                ]
            );
        $result = $this->helper->isDebugLog();
        $this->assertEquals($result, $scopeResult);
    }
}
