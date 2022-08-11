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

namespace CrowdSec\Bouncer\Controller\Adminhtml\System\Config\Cache;

use CrowdSec\Bouncer\Controller\Adminhtml\System\Config\Action;
use Exception;
use LogicException;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use CrowdSec\Bouncer\Registry\CurrentBounce as RegistryBounce;
use CrowdSec\Bouncer\Helper\Data as Helper;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;

class Clear extends Action implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var RegistryBounce
     */
    protected $registryBounce;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param RegistryBounce $registryBounce
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        RegistryBounce $registryBounce,
        Helper $helper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->registryBounce = $registryBounce;
        $this->helper = $helper;
    }

    /**
     * Clear cache
     *
     * @return Json
     * @throws LogicException
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    public function execute(): Json
    {
        try {
            if (!($bounce = $this->registryBounce->get())) {
                $bounce = $this->registryBounce->create();
            }

            $configs = $this->helper->getBouncerConfigs();
            $bouncer = $bounce->init($configs);
            $result = (int) $bouncer->clearCache();
            $cacheSystem = $this->helper->getCacheTechnology();
            $cacheOptions = $this->helper->getCacheSystemOptions();
            $cacheLabel = $cacheOptions[$cacheSystem] ?? __('Unknown');
            $message = __('CrowdSec cache (%1) has been cleared.', $cacheLabel);
            if ($this->helper->isStreamModeEnabled()) {
                $warmUpCacheResult = $bouncer->warmBlocklistCacheUp();
                $decisionsCount = $warmUpCacheResult['count']??0;
                $decisionsMessage =
                    $decisionsCount > 1 ? 'There are now %1 decisions in cache.' : 'There is now %1 decision in cache.';
                $message .=' '. __('As the stream mode is enabled, cache has been warmed up too.', $cacheLabel);
                $message .=  ' '.__("$decisionsMessage", $decisionsCount);
            }

        } catch (Exception $e) {
            $this->helper->error('', [
                'type' => 'M2_EXCEPTION_WHILE_CLEARING_CACHE',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $result = false;
            $message = __('Technical error while clearing the cache: ' . $e->getMessage());
        }

        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData([
            'cleared' => $result,
            'message' => $message,
        ]);
    }
}
