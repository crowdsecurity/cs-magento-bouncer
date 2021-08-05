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
use Magento\Framework\App\Config\Storage\WriterInterface;
use CrowdSecBouncer\RestClient;

/**
 * Plugin to handle crowdsec section config updates
 */
class Config
{
    /**
     * @see https://devdocs.magento.com/guides/v2.4/config-guide/cron/custom-cron-ref.html#disable-cron-job
     */
    const CRON_DISABLE = '0 0 30 2 *';

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /** @var  RestClient */
    protected $restClient;

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
        RegistryBouncer $registryBouncer,
        WriterInterface $configWriter,
        RestClient $restClient
    ) {
        $this->messageManager = $messageManager;
        $this->helper = $helper;
        $this->registryBouncer = $registryBouncer;
        $this->configWriter = $configWriter;
        $this->restClient = $restClient;
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
            // Retrieve saved values (old) and posted data (new)
            $oldStreamMode = $this->helper->isStreamModeEnabled();
            $newStreamMode = $subject->getData(Helper::STREAM_MODE_FULL_PATH) === null
                ? $oldStreamMode :
                (bool)$subject->getData(Helper::STREAM_MODE_FULL_PATH);

            $oldMemcachedDsn = $this->helper->getMemcachedDSN();
            $newMemcachedDsn = $this->getCurrentValue(
                $subject->getData(Helper::MEMCACHED_DSN_FULL_PATH),
                $oldMemcachedDsn
            );

            $oldRedisDsn = $this->helper->getRedisDSN();
            $newRedisDsn = $this->getCurrentValue(
                $subject->getData(Helper::REDIS_DSN_FULL_PATH),
                $oldRedisDsn
            );

            $oldCacheSystem = $this->helper->getCacheTechnology();
            $newCacheSystem = $this->getCurrentValue(
                $subject->getData(Helper::CACHE_TECHNOLOGY_FULL_PATH),
                $oldCacheSystem
            );

            $oldRefreshCronExpr = $this->helper->getRefreshCronExpr();
            $newRefreshCronExpr = $this->getCurrentValue(
                $subject->getData(Helper::REFRESH_CRON_EXPR_FULL_PATH),
                $oldRefreshCronExpr
            );

            $oldUrl = $this->helper->getApiUrl();
            $newUrl = $this->getCurrentValue(
                $subject->getData(Helper::API_URL_FULL_PATH),
                $oldUrl
            );

            $oldKey = $this->helper->getApiKey();
            $newKey = $this->getCurrentValue(
                $subject->getData(Helper::API_KEY_FULL_PATH),
                $oldKey
            );

            $cacheOptions = $this->helper->getCacheSystemOptions();
            $oldCacheLabel = $cacheOptions[$oldCacheSystem] ?? __('Unknown');
            $newCacheLabel = $cacheOptions[$newCacheSystem] ?? __('Unknown');
            $hasCacheSystemChanged = $oldCacheSystem !== $newCacheSystem;
            $hasDsnChanged = $this->hasDsnChanged(
                $newCacheSystem,
                $oldRedisDsn,
                $newRedisDsn,
                $oldMemcachedDsn,
                $newMemcachedDsn
            );
            $cacheChanged = ($hasCacheSystemChanged || $hasDsnChanged);
            $refreshCronExprChanged = $oldRefreshCronExpr !== $newRefreshCronExpr;
            // We should have to test connection
            $this->_handleConnectionChanges($oldUrl, $newUrl, $oldKey, $newKey);
            // We should have to deactivate or test cron
            $this->_handleRefreshCronExpr($oldStreamMode, $newStreamMode, $refreshCronExprChanged, $newRefreshCronExpr);
            // We should have to test new cache and clear old cache
            $this->_handleTestCache(
                $cacheChanged,
                $newCacheSystem,
                $newMemcachedDsn,
                $newRedisDsn,
                $newCacheLabel
            );
            $this->_handleOldClearCache(
                $cacheChanged,
                $oldCacheSystem,
                $oldMemcachedDsn,
                $oldRedisDsn,
                $oldCacheLabel
            );
            // Stream mode changes could imply some particular tasks
            $this->_handleStreamMode(
                $oldStreamMode,
                $newStreamMode,
                $refreshCronExprChanged,
                $cacheChanged,
                $newCacheSystem,
                $newRedisDsn,
                $newMemcachedDsn,
                $newCacheLabel
            );
        }

        return null;
    }

    private function getCurrentValue($subject, $saved)
    {

        return $subject ?:$saved;
    }

    /**
     * Handle Stream Mode specificity
     * @param bool $oldStreamMode
     * @param bool $newStreamMode
     * @param bool $refreshCronExprChanged
     * @param bool $cacheChanged
     * @param string $newCacheSystem
     * @param string $newRedisDsn
     * @param string $newMemcachedDsn
     * @param Phrase $newCacheLabel
     * @throws CrowdsecException
     */
    public function _handleStreamMode(
        bool $oldStreamMode,
        bool $newStreamMode,
        bool $refreshCronExprChanged,
        bool $cacheChanged,
        string $newCacheSystem,
        string $newRedisDsn,
        string $newMemcachedDsn,
        Phrase $newCacheLabel
    ) {
        // We should have to warm up the cache in Stream Mode
        $this->_handleWarmUp(
            $oldStreamMode,
            $newStreamMode,
            $refreshCronExprChanged,
            $cacheChanged,
            $newCacheSystem,
            $newRedisDsn,
            $newMemcachedDsn,
            $newCacheLabel
        );
        // We have to clear new cache if we just have deactivate Stream Mode
        $this->_handleNewClearCache(
            $oldStreamMode,
            $newStreamMode,
            $newCacheSystem,
            $newRedisDsn,
            $newMemcachedDsn,
            $newCacheLabel
        );
    }

    /**
     * Warm up the cache if necessary
     * @param bool $oldStreamMode
     * @param bool $newStreamMode
     * @param bool $refreshCronExprChanged,
     * @param bool $cacheChanged
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
        bool $cacheChanged,
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
            if ($cacheChanged) {
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
     * @throws CrowdsecException
     */
    protected function _handleRefreshCronExpr(
        bool $oldStreamMode,
        bool $newStreamMode,
        bool $refreshCronExprChanged,
        string $newRefreshCronExpr
    ) {
        // Disable cache refresh cron if Stream Mode deactivated
        if ($oldStreamMode !== $newStreamMode && $newStreamMode === false) {
            try {
                $this->configWriter->save(
                    Helper::XML_PATH_ADVANCED_REFRESH_CRON_EXPR,
                    self::CRON_DISABLE
                );
                $cronMessage = __('Cache refresh cron has been disabled.');
                $this->messageManager->addNoticeMessage($cronMessage);
            } catch (Exception $e) {
                throw new CrowdsecException(__('Disabled refresh expression cron can\'t be saved: ', $e->getMessage()));
            }
        } elseif ($refreshCronExprChanged) {
            try {
                $this->helper->validateCronExpr($newRefreshCronExpr);
            } catch (Exception $e) {
                throw new CrowdsecException(__(
                    'Refresh expression cron (%1) is not valid.',
                    $newRefreshCronExpr
                ));
            }
        }
    }

    /**
     * @param string $oldUrl
     * @param string $newUrl
     * @param string $oldKey
     * @param string $newKey
     * @throws CrowdsecException
     */
    protected function _handleConnectionChanges(
        string $oldUrl,
        string $newUrl,
        string $oldKey,
        string $newKey
    ) {
        // Test connection if params changed
        if ($oldUrl !== $newUrl || $oldKey !== $newKey) {
            try {
                $this->helper->ping($this->restClient, $newUrl, Constants::BASE_USER_AGENT, $newKey);
            } catch (Exception $e) {
                throw new CrowdsecException(__('Connection test failed with url "%1" and key "%2"', $newUrl, $newKey));
            }
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
    protected function _handleNewClearCache(
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
     * Clear old cache if necessary
     * @param bool $cacheChanged
     * @param string $oldCacheSystem
     * @param string $oldMemcachedDsn
     * @param string $oldRedisDsn
     * @param Phrase $oldCacheLabel
     */
    protected function _handleOldClearCache(
        bool $cacheChanged,
        string $oldCacheSystem,
        string $oldMemcachedDsn,
        string $oldRedisDsn,
        Phrase $oldCacheLabel
    ) {
        if ($cacheChanged) {
            if (!($bouncer = $this->registryBouncer->get())) {
                $bouncer = $this->registryBouncer->create();
            }
            $this->_clearCache($bouncer, $oldCacheSystem, $oldMemcachedDsn, $oldRedisDsn, $oldCacheLabel);
        }
    }

    /**
     * Test a cache configuration for some bouncer
     * @param bool $cacheChanged
     * @param string $cacheSystem
     * @param string $memcachedDsn
     * @param string $redisDsn
     * @param Phrase $cacheLabel
     * @throws CrowdsecException
     */
    protected function _handleTestCache(
        bool $cacheChanged,
        string $cacheSystem,
        string $memcachedDsn,
        string $redisDsn,
        Phrase $cacheLabel
    ): void {
        if ($cacheChanged) {
            try {
                // Try the adapter connection (Redis or Memcached will crash if the connection is incorrect)
                if (!($bouncer = $this->registryBouncer->get())) {
                    $bouncer = $this->registryBouncer->create();
                }
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
