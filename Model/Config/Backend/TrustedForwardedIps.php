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

namespace Crowdsec\Bouncer\Model\Config\Backend;

use Crowdsec\Bouncer\Helper\Config;
use Exception;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Crowdsec\Bouncer\Exception\CrowdsecException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use IPLib\Factory;

/**
 * Serialized backend model for Ips and Ips range
 *
 */
class TrustedForwardedIps extends Value
{

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param WriterInterface $configWriter
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param Json $serializer
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        WriterInterface $configWriter,
        Json $serializer,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configWriter = $configWriter;
        $this->serializer = $serializer;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     *
     * Update array config for Ips
     * @return Value
     * @throws CrowdsecException
     */
    public function afterSave(): Value
    {
        if ($this->isValueChanged()) {
            $comparableIpBoundsList = [];
            if (!empty($this->getValue())) {
                $stringRangeArray = explode(Config::TEXT_SEPARATOR, $this->getValue());
                foreach ($stringRangeArray as $stringRange) {
                    $stringRange = trim($stringRange);
                    if (false !== strpos($stringRange, '/')) {
                        $range = Factory::rangeFromString($stringRange);
                        if (null === $range) {
                            throw new CrowdsecException(__('Invalid IP List format.'));
                        }
                        $bounds = [$range->getComparableStartString(), $range->getComparableEndString()];
                        $comparableIpBoundsList = [$bounds];
                    } else {
                        $address = Factory::addressFromString($stringRange);
                        if (null === $address) {
                            throw new CrowdsecException(__('Invalid IP List format.'));
                        }
                        $comparableString = $address->getComparableString();
                        $comparableIpBoundsList[] = [$comparableString, $comparableString];
                    }
                }
            }

            try {
                $this->configWriter->save(
                    Config::TRUSTED_FORWARD_IPS_PATH,
                    $this->serializer->serialize(($comparableIpBoundsList)),
                    $this->getScope() ?:
                        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    $this->getScopeCode()
                );
            } catch (Exception $e) {
                $this->_logger->error($e->getMessage());
                throw new CrowdsecException(__('Crowdsec Trusted forward ips settings can\'t be saved'));
            }
        }

        return parent::afterSave();
    }
}
