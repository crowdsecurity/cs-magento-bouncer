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

namespace CrowdSec\Bouncer\Model\Config\Backend;

use CrowdSec\Bouncer\Helper\Config;
use CrowdSecBouncer\BouncerException;
use Exception;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
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
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param WriterInterface $configWriter
     * @param Json $serializer
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        WriterInterface $configWriter,
        Json $serializer,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configWriter = $configWriter;
        $this->serializer = $serializer;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Update array config for Ips
     *
     * @return Value
     * @throws BouncerException
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
                        $range = Factory::parseRangeString($stringRange);
                        if (null === $range) {
                            throw new BouncerException('Invalid IP List format.');
                        }
                        $bounds = [$range->getComparableStartString(), $range->getComparableEndString()];
                        $comparableIpBoundsList = [$bounds];
                    } else {
                        $address = Factory::parseAddressString($stringRange, 3);
                        if (null === $address) {
                            throw new BouncerException('Invalid IP List format.');
                        }
                        $comparableString = $address->getComparableString();
                        $comparableIpBoundsList[] = [$comparableString, $comparableString];
                    }
                }
            }

            try {
                $this->configWriter->save(
                    Config::TRUSTED_FORWARD_IPS_PATH,
                    $this->serializer->serialize(($comparableIpBoundsList))
                );
            } catch (Exception $e) {
                $this->_logger->error($e->getMessage());
                throw new BouncerException('CrowdSec Trusted forward ips settings can\'t be saved');
            }
        }

        return parent::afterSave();
    }
}
