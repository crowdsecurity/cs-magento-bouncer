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

namespace Crowdsec\Bouncer\Plugin;

use Crowdsec\Bouncer\Exception\CrowdsecException;
use Crowdsec\Bouncer\Helper\Data as Helper;
use Crowdsec\Bouncer\Model\Bouncer;
use Crowdsec\Bouncer\Registry\CurrentBouncer as RegistryBouncer;
use Exception;
use Magento\Framework\Message\ManagerInterface;
use Crowdsec\Bouncer\Constants;
use Magento\Framework\Phrase;

/**
 * Plugin to handle crowdsec section config updates
 */
class Config
{

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var RegistryBouncer
     */
    protected $registryBouncer;

    public function __construct(
        ManagerInterface $messageManager,
        Helper $helper,
        RegistryBouncer $registryBouncer
    ) {
        $this->messageManager = $messageManager;
        $this->helper = $helper;
        $this->registryBouncer = $registryBouncer;
    }

    /**
     * Handle admin Crowdsec section changes
     * @param \Magento\Config\Model\Config $subject
     * @return null
     * @throws CrowdsecException
     */
    public function beforeSave(
        \Magento\Config\Model\Config $subject
    ) {
        if ($subject->getSection() === Helper::SECTION) {
            $oldMemcachedDsn = $this->helper->getMemcachedDSN();
            $oldRedisDsn = $this->helper->getRedisDSN();
            $oldCacheSystem = $this->helper->getCacheTechnology();
            $oldStreamMode = $this->helper->isStreamModeEnabled();
            $newStreamMode = $subject->getData(Helper::STREAM_MODE_FULL_PATH) === null
                ? $oldStreamMode :
                (bool)$subject->getData(Helper::STREAM_MODE_FULL_PATH);
            $oldRefreshCronExpr = $this->helper->getRefreshCronExpr();
            $newRefreshCronExpr = $subject->getData(Helper::REFRESH_CRON_EXPR_FULL_PATH) ?: $oldRefreshCronExpr;
            $newMemcachedDsn = $subject->getData(Helper::MEMCACHED_DSN_FULL_PATH) ?: $oldMemcachedDsn;
            $newRedisDsn = $subject->getData(Helper::REDIS_DSN_FULL_PATH) ?: $oldRedisDsn;
            $newCacheSystem = $subject->getData(Helper::CACHE_TECHNOLOGY_FULL_PATH) ?: $oldCacheSystem;
            $cacheOptions = $this->helper->getCacheSystemOptions();
            $oldCacheLabel = $cacheOptions[$oldCacheSystem] ?? __('Unknown');
            $newCacheLabel = $cacheOptions[$newCacheSystem] ?? __('Unknown');
            $hasCacheChanged = $oldCacheSystem !== $newCacheSystem;
            $hasDsnChanged = $this->hasDsnChanged(
                $newCacheSystem,
                $oldRedisDsn,
                $newRedisDsn,
                $oldMemcachedDsn,
                $newMemcachedDsn
            );

            // We should have to test new cache and clear old cache
            if ($hasCacheChanged || $hasDsnChanged) {
                $bouncer = $this->registryBouncer->create();
                $this->_testCache($bouncer, $newCacheSystem, $newMemcachedDsn, $newRedisDsn, $newCacheLabel);
                $this->_clearCache($bouncer, $oldCacheSystem, $oldMemcachedDsn, $oldRedisDsn, $oldCacheLabel);

            }
            $refreshCronExprChanged = $oldRefreshCronExpr !== $newRefreshCronExpr;
            // We should have to warm up the cache in Stream Mode
            $this->_handleWarmUp(
                $oldStreamMode,
                $newStreamMode,
                $refreshCronExprChanged,
                $hasCacheChanged,
                $hasDsnChanged,
                $newCacheSystem,
                $newRedisDsn,
                $newMemcachedDsn,
                $newCacheLabel
            );
            // We have to clear new cache if we just have deactivate Stream Mode
            $this->_handleClearCache(
                $oldStreamMode,
                $newStreamMode,
                $newCacheSystem,
                $newRedisDsn,
                $newMemcachedDsn,
                $newCacheLabel
            );
        }

        return null;
    }

    /**
     * Warm up the cache if necessary
     * @param bool $oldStreamMode
     * @param bool $newStreamMode
     * @param bool $refreshCronExprChanged,
     * @param bool $hasCacheChanged
     * @param bool $hasDsnChanged
     * @param string $newCacheSystem
     * @param string $newRedisDsn
     * @param string $newMemcachedDsn
     * @param Phrase $newCacheLabel
     * @throws CrowdsecException
     */
    protected function _handleWarmUp(
        bool $oldStreamMode,
        bool $newStreamMode,
        bool $refreshCronExprChanged,
        bool $hasCacheChanged,
        bool $hasDsnChanged,
        string $newCacheSystem,
        string $newRedisDsn,
        string $newMemcachedDsn,
        Phrase $newCacheLabel
    ) {
        $shouldWarmUp = false;
        if ($newStreamMode === true) {
            if ($oldStreamMode !== $newStreamMode) {
                $shouldWarmUp = true;

            }
            if ($refreshCronExprChanged) {
                $shouldWarmUp = true;
            }
            if ($hasCacheChanged || $hasDsnChanged) {
                $shouldWarmUp = true;
            }
        }

        if ($shouldWarmUp) {
            if (!($bouncer = $this->registryBouncer->get())) {
                $bouncer = $this->registryBouncer->create();
            }
            $this->_warmUpCache($bouncer, $newCacheSystem, $newMemcachedDsn, $newRedisDsn, $newCacheLabel);
        }
    }

    /**
     * Clear current cache if necessary
     * @param bool $oldStreamMode
     * @param bool $newStreamMode
     * @param string $newCacheSystem
     * @param string $newRedisDsn
     * @param string $newMemcachedDsn
     * @param Phrase $newCacheLabel
     */
    protected function _handleClearCache(
        bool $oldStreamMode,
        bool $newStreamMode,
        string $newCacheSystem,
        string $newRedisDsn,
        string $newMemcachedDsn,
        Phrase $newCacheLabel
    ) {

        $shouldClearCache = false;
        if ($newStreamMode === false) {
            if ($oldStreamMode !== $newStreamMode) {
                $shouldClearCache = true;
            }
        }

        if ($shouldClearCache) {
            if (!($bouncer = $this->registryBouncer->get())) {
                $bouncer = $this->registryBouncer->create();
            }
            $preMessage = __('As the stream mode has been deactivated: ');
            $this->_clearCache($bouncer, $newCacheSystem, $newMemcachedDsn, $newRedisDsn, $newCacheLabel, $preMessage);
        }
    }

    /**
     * Test a cache configuration for some bouncer
     * @param Bouncer $bouncer
     * @param string $cacheSystem
     * @param string $memcachedDsn
     * @param string $redisDsn
     * @param Phrase $cacheLabel
     * @throws CrowdsecException
     */
    protected function _testCache(
        Bouncer $bouncer,
        string $cacheSystem,
        string $memcachedDsn,
        string $redisDsn,
        Phrase $cacheLabel
    ): void {
        try {
            // Try the adapter connection (Redis or Memcached will crash if the connection is incorrect)
            $bouncer->init(
                [
                    'forced_cache_system' => $cacheSystem,
                    'memcached_dsn' => $memcachedDsn,
                    'redis_dsn' => $redisDsn
                ]
            )->testConnection();
            $cacheMessage = __('CrowdSec new cache (%1) has been successfully tested.', $cacheLabel);
            $this->messageManager->addNoticeMessage($cacheMessage);
        } catch (Exception $e) {
            $this->helper->error('', [
                'type' => 'M2_EXCEPTION_WHILE_TESTING_CACHE',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $cacheMessage =
                __('Technical error while testing the %1 cache: ' . $e->getMessage(), $cacheLabel);
            throw new CrowdsecException(__($cacheMessage));
        }
    }

    /**
     * Clear a cache for some config and bouncer
     * @param Bouncer $bouncer
     * @param string $cacheSystem
     * @param string $memcachedDsn
     * @param string $redisDsn
     * @param Phrase $cacheLabel
     * @param null|string $preMessage
     */
    protected function _clearCache(
        Bouncer $bouncer,
        string $cacheSystem,
        string $memcachedDsn,
        string $redisDsn,
        Phrase $cacheLabel,
        $preMessage = null
    ): void {
        try {
            $clearCacheResult =
                $bouncer->init(
                    [
                        'forced_cache_system' => $cacheSystem,
                        'memcached_dsn' => $memcachedDsn,
                        'redis_dsn' => $redisDsn
                    ]
                )->clearCache();
            $this->displayCacheClearMessage($clearCacheResult, $cacheLabel, $preMessage);
        } catch (CrowdsecException $e) {
            $this->helper->error('', [
                'type' => 'M2_EXCEPTION_WHILE_CLEARING_CACHE',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $clearCacheMessage = $preMessage .
                __('Technical error while clearing the %1 cache: ' . $e->getMessage(), $cacheLabel);
            $this->messageManager->addWarningMessage($clearCacheMessage);
        }
    }

    /**
     * @param Bouncer $bouncer
     * @param $cacheSystem
     * @param $memcachedDsn
     * @param $redisDsn
     * @param $cacheLabel
     * @throws CrowdsecException
     */
    protected function _warmUpCache(Bouncer $bouncer, $cacheSystem, $memcachedDsn, $redisDsn, $cacheLabel): void
    {
        try {
            $warmUpCacheResult =
                $bouncer->init(
                    [
                        'forced_cache_system' => $cacheSystem,
                        'memcached_dsn' => $memcachedDsn,
                        'redis_dsn' => $redisDsn
                    ]
                )->warmBlocklistCacheUp();
            $this->displayCacheWarmUpMessage($warmUpCacheResult, $cacheLabel);
        } catch (CrowdsecException $e) {
            $this->helper->error('', [
                'type' => 'M2_EXCEPTION_WHILE_WARMING_UP_CACHE',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $cacheMessage =
                __('Technical error while warming up the %1 cache: ' . $e->getMessage(), $cacheLabel);
            throw new CrowdsecException(__($cacheMessage));
        }
    }

    private function hasDsnChanged($newCacheSystem, $oldRedisDsn, $newRedisDsn, $oldMemcachedDsn, $newMemcachedDsn):
    bool
    {
        switch ($newCacheSystem) {
            case Constants::CACHE_SYSTEM_REDIS:
                return $oldRedisDsn !== $newRedisDsn;
            case Constants::CACHE_SYSTEM_MEMCACHED:
                return $oldMemcachedDsn !== $newMemcachedDsn;
            default:
                return false;
        }
    }

    private function displayCacheClearMessage($clearCacheResult, $cacheLabel, $preMessage = null): void
    {
        $clearCacheMessage =
            $clearCacheResult ? __('%1 cache has been cleared.', $cacheLabel) :
                __('%1 cache has not been cleared.', $cacheLabel);
        $this->messageManager->addNoticeMessage($preMessage .$clearCacheMessage);
    }

    private function displayCacheWarmUpMessage($warmUpCacheResult, $cacheLabel): void
    {

        $decisionsCount = $warmUpCacheResult['count']??0;
        $decisionsMessage =
            $decisionsCount > 1 ? 'There are now %1 decisions in cache.' : 'There is now %1 decision in cache.';
        $message = __('As the stream mode is enabled, the cache (%1) has been warmed up.', $cacheLabel);
        $message .=  ' '.__("$decisionsMessage", $decisionsCount);

        $this->messageManager->addNoticeMessage($message);
    }
}
