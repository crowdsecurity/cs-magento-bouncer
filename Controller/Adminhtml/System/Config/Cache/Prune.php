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
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use CrowdSec\Bouncer\Registry\CurrentBouncer as RegistryBouncer;
use CrowdSec\Bouncer\Exception\CrowdSecException;
use CrowdSec\Bouncer\Helper\Data as Helper;
use CrowdSec\Bouncer\Constants;

class Prune extends Action implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var RegistryBouncer
     */
    protected $registryBouncer;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param RegistryBouncer $registryBouncer
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        RegistryBouncer $registryBouncer,
        Helper $helper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->registryBouncer = $registryBouncer;
        $this->helper = $helper;
    }

    /**
     * Prune cache
     *
     * @return Json
     */
    public function execute(): Json
    {
        try {
            if (!($bouncer = $this->registryBouncer->get())) {
                $bouncer = $this->registryBouncer->create();
            }
            $configs= $this->helper->getBouncerConfigs();
            $result = (int) $bouncer->init($configs, ['forced_cache_system' => Constants::CACHE_SYSTEM_PHPFS])
                ->pruneCache();
            $cacheOptions = $this->helper->getCacheSystemOptions();
            $cacheLabel = $cacheOptions[Constants::CACHE_SYSTEM_PHPFS] ?? __('Unknown');
            $message = __('CrowdSec cache (%1) has been pruned.', $cacheLabel);

        } catch (CrowdSecException $e) {
            $this->helper->error('', [
                'type' => 'M2_EXCEPTION_WHILE_PRUNING_CACHE',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $result = false;
            $message = __('Technical error while pruning the cache: ' . $e->getMessage());
        }

        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData([
            'pruned' => $result,
            'message' => $message,
        ]);
    }
}
