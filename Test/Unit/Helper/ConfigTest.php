<?php
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

namespace CrowdSec\Bouncer\Test\Unit\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use CrowdSec\Bouncer\Helper\Config;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    protected $helper;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $className = Config::class;
        $arguments = $objectManagerHelper->getConstructArguments($className);

        /** @var Context $context */
        $context = $arguments['context'];
        $this->scopeConfig = $context->getScopeConfig();

        $this->helper = $objectManagerHelper->getObject($className, $arguments);
    }

    /**
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testIsFrontEnabled()
    {
        $this->scopeConfig->expects($this->once())->method('getValue')->with(
            Config::XML_PATH_FRONT_ENABLED,
            ScopeInterface::SCOPE_STORE
        )
            ->willReturn(true);
        $result = $this->helper->isFrontEnabled();

        $this->assertEquals(true, $result);
    }

    /**
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testIsFrontDisabled()
    {
        $this->scopeConfig->expects($this->once())->method('getValue')->with(
            Config::XML_PATH_FRONT_ENABLED,
            ScopeInterface::SCOPE_STORE
        )
            ->willReturn(false);
        $result = $this->helper->isFrontEnabled();

        $this->assertEquals(false, $result);
    }

    /**
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testIsAdminEnabled()
    {
        $this->scopeConfig->expects($this->once())->method('getValue')->with(
            Config::XML_PATH_ADMIN_ENABLED
        )
            ->willReturn(true);
        $result = $this->helper->isAdminEnabled();

        $this->assertEquals(true, $result);
    }

    /**
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testIsAdminDisabled()
    {
        $this->scopeConfig->expects($this->once())->method('getValue')->with(
            Config::XML_PATH_ADMIN_ENABLED
        )
            ->willReturn(false);
        $result = $this->helper->isAdminEnabled();

        $this->assertEquals(false, $result);
    }

    /**
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testIsDebugLog()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    [
                        Config::XML_PATH_ADVANCED_DEBUG_LOG,
                        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        null,
                        true,
                    ]

                ]
            );
        $result = $this->helper->isDebugLog();
        $this->assertEquals(true, $result);
    }

    /**
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testIsNotDebugLog()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    [
                        Config::XML_PATH_ADVANCED_DEBUG_LOG,
                        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        null,
                        false,
                    ]

                ]
            );
        $result = $this->helper->isDebugLog();
        $this->assertEquals(false, $result);
    }
}
