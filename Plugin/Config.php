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

namespace CrowdSec\Bouncer\Plugin;

use CrowdSec\Bouncer\Exception\CrowdSecException;
use CrowdSec\Bouncer\Helper\Data as Helper;
use CrowdSec\Bouncer\Registry\CurrentBouncer as RegistryBouncer;
use Exception;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Message\ManagerInterface;
use CrowdSec\Bouncer\Constants;
use Magento\Framework\Phrase;
use Magento\Framework\App\Config\Storage\WriterInterface;
use CrowdSecBouncer\RestClient;
use Psr\Cache\InvalidArgumentException;
use Magento\Config\Model\Config as MagentoConfig;

/**
 * Plugin to handle crowdsec section config updates
 */
class Config
{
    /**
     * @see https://devdocs.magento.com/guides/v2.4/config-guide/cron/custom-cron-ref.html#disable-cron-job
     */
    public const CRON_DISABLE = '0 0 30 2 *';

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

    /**
     * Constructor
     *
     * @param ManagerInterface $messageManager
     * @param Helper $helper
     * @param RegistryBouncer $registryBouncer
     * @param WriterInterface $configWriter
     * @param RestClient $restClient
     */
    public function __construct(
        ManagerInterface $messageManager,
        Helper           $helper,
        RegistryBouncer  $registryBouncer,
        WriterInterface  $configWriter,
        RestClient       $restClient
    ) {
        $this->messageManager = $messageManager;
        $this->helper = $helper;
        $this->registryBouncer = $registryBouncer;
        $this->configWriter = $configWriter;
        $this->restClient = $restClient;
    }

    /**
     * Handle admin CrowdSec section changes
     *
     * @param MagentoConfig $subject
     * @return null
     * @throws CrowdSecException|InvalidArgumentException
     * @throws FileSystemException
     */
    public function beforeSave(
        MagentoConfig $subject
    ) {
        if ($subject->getSection() === Helper::SECTION) {
            // Retrieve saved values (old) and posted data (new)
            $oldStreamMode = $this->helper->isStreamModeEnabled();
            $newStreamMode = $subject->getData(Helper::STREAM_MODE_FULL_PATH) === null ? $oldStreamMode :
                (bool)$subject->getData(Helper::STREAM_MODE_FULL_PATH);
            $oldMemcachedDsn = $this->helper->getMemcachedDSN();
            $newMemcachedDsn = $this->getCurrentValue(
                $subject->getData(Helper::MEMCACHED_DSN_FULL_PATH),
                $oldMemcachedDsn
            );
            $oldRedisDsn = $this->helper->getRedisDSN();
            $newRedisDsn = $this->getCurrentValue($subject->getData(Helper::REDIS_DSN_FULL_PATH), $oldRedisDsn);
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
            $oldPruneCronExpr = $this->helper->getPruneCronExpr();
            $newPruneCronExpr = $this->getCurrentValue(
                $subject->getData(Helper::PRUNE_CRON_EXPR_FULL_PATH),
                $oldPruneCronExpr
            );
            $oldUrl = $this->helper->getApiUrl();
            $newUrl = $this->getCurrentValue($subject->getData(Helper::API_URL_FULL_PATH), $oldUrl);
            $oldKey = $this->helper->getApiKey();
            $newKey = $this->getCurrentValue($subject->getData(Helper::API_KEY_FULL_PATH), $oldKey);
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
            $pruneCronExprChanged = $oldPruneCronExpr !== $newPruneCronExpr;
            // We should have to test connection
            $this->_handleConnectionChanges($oldUrl, $newUrl, $oldKey, $newKey);
            // We should have to deactivate or test cron
            $this->_handleRefreshCronExpr($oldStreamMode, $newStreamMode, $refreshCronExprChanged, $newRefreshCronExpr);
            // We should have to test cron
            $this->_handlePruneCronExpr($oldCacheSystem, $newCacheSystem, $pruneCronExprChanged, $newPruneCronExpr);
            // We should have to test new cache and clear old cache
            $this->_handleTestCache($cacheChanged, $newCacheSystem, $newMemcachedDsn, $newRedisDsn, $newCacheLabel);
            $this->_handleOldClearCache($cacheChanged, $oldCacheSystem, $oldMemcachedDsn, $oldRedisDsn, $oldCacheLabel);
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

    /**
     * Get a configuration current value
     *
     * @param mixed $subject
     * @param mixed $saved
     * @return mixed
     */
    private function getCurrentValue($subject, $saved)
    {
        return $subject ?: $saved;
    }

    /**
     * Handle Stream Mode specificity
     *
     * @param bool $oldStreamMode
     * @param bool $newStreamMode
     * @param bool $refreshCronExprChanged
     * @param bool $cacheChanged
     * @param string $newCacheSystem
     * @param string $newRedisDsn
     * @param string $newMemcachedDsn
     * @param Phrase $newCacheLabel
     * @throws InvalidArgumentException
     * @throws FileSystemException
     */
    public function _handleStreamMode(
        bool   $oldStreamMode,
        bool   $newStreamMode,
        bool   $refreshCronExprChanged,
        bool   $cacheChanged,
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
        // We have to clear new cache if we just have deactivated Stream Mode
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
     *
     * @param bool $oldStreamMode
     * @param bool $newStreamMode
     * @param bool $refreshCronExprChanged ,
     * @param bool $cacheChanged
     * @param string $newCacheSystem
     * @param string $newRedisDsn
     * @param string $newMemcachedDsn
     * @param Phrase $newCacheLabel
     * @throws InvalidArgumentException
     * @throws FileSystemException
     */
    protected function _handleWarmUp(
        bool   $oldStreamMode,
        bool   $newStreamMode,
        bool   $refreshCronExprChanged,
        bool   $cacheChanged,
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
            $this->_warmUpCache($newCacheSystem, $newMemcachedDsn, $newRedisDsn, $newCacheLabel);
        }
    }

    /**
     * Handle refresh expression cron
     *
     * @param bool $oldStreamMode
     * @param bool $newStreamMode
     * @param bool $cronExprChanged
     * @param string $newCronExpr
     * @return void
     */
    protected function _handleRefreshCronExpr(
        bool   $oldStreamMode,
        bool   $newStreamMode,
        bool   $cronExprChanged,
        string $newCronExpr
    ) {
        if ($oldStreamMode !== $newStreamMode && $newStreamMode === false && $newCronExpr !== self::CRON_DISABLE) {
            // Disable cache refresh cron if Stream Mode deactivated
            try {
                $this->configWriter->save(
                    Helper::XML_PATH_ADVANCED_REFRESH_CRON_EXPR,
                    self::CRON_DISABLE
                );
                $cronMessage = __('Cache refresh cron has been disabled.');
                $this->messageManager->addNoticeMessage($cronMessage);
            } catch (Exception $e) {
                throw new CrowdSecException('Disabled refresh cron expression can\'t be saved: '. $e->getMessage());
            }
        } elseif ($cronExprChanged) {
            // Check expression
            try {
                $this->helper->validateCronExpr($newCronExpr);
            } catch (Exception $e) {
                $this->messageManager->getMessages(true);
                throw new CrowdSecException("Refresh cron expression ($newCronExpr) is not valid.");
            }
        }
    }

    /**
     * Handle prune cron expression cron
     *
     * @param string $oldCacheSystem
     * @param string $newCacheSystem
     * @param bool $cronExprChanged
     * @param string $newCronExpr
     * @return void
     */
    protected function _handlePruneCronExpr(
        string $oldCacheSystem,
        string $newCacheSystem,
        bool   $cronExprChanged,
        string $newCronExpr
    ) {
        if ($oldCacheSystem !== $newCacheSystem &&
                  $newCacheSystem !== Constants::CACHE_SYSTEM_PHPFS
                  && $newCronExpr !== self::CRON_DISABLE) {
            // Disable cache pruning cron if cache technology is not file system
            try {
                $this->configWriter->save(
                    Helper::XML_PATH_ADVANCED_PRUNE_CRON_EXPR,
                    self::CRON_DISABLE
                );
                $cronMessage = __('File system cache pruning cron has been disabled.');
                $this->messageManager->addNoticeMessage($cronMessage);
            } catch (Exception $e) {
                throw new CrowdSecException('Disabled pruning cron expression can\'t be saved: '. $e->getMessage());
            }
        } elseif ($cronExprChanged) {
            // Check expression
            try {
                $this->helper->validateCronExpr($newCronExpr);
            } catch (Exception $e) {
                $this->messageManager->getMessages(true);
                throw new CrowdSecException("Pruning cron expression ($newCronExpr) is not valid.");
            }
        }
    }

    /**
     * Handle connection changes
     *
     * @param string $oldUrl
     * @param string $newUrl
     * @param string $oldKey
     * @param string $newKey
     * @return void
     */
    protected function _handleConnectionChanges(
        string $oldUrl,
        string $newUrl,
        string $oldKey,
        string $newKey
    ) {
        // Test connection if params changed
        if (($newUrl && $newKey) && ($oldUrl !== $newUrl || $oldKey !== $newKey)) {
            try {
                $this->helper->ping($this->restClient, $newUrl, Constants::BASE_USER_AGENT, $newKey);
            } catch (Exception $e) {
                throw new CrowdSecException("Connection test failed with url \'$newUrl\' and key \'$newKey\'");
            }
        }
    }

    /**
     * Clear current cache if necessary
     *
     * @param bool $oldStreamMode
     * @param bool $newStreamMode
     * @param string $newCacheSystem
     * @param string $newRedisDsn
     * @param string $newMemcachedDsn
     * @param Phrase $newCacheLabel
     * @throws InvalidArgumentException
     * @throws FileSystemException
     */
    protected function _handleNewClearCache(
        bool   $oldStreamMode,
        bool   $newStreamMode,
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
            $preMessage = __('As the stream mode has been deactivated: ');
            $this->_clearCache($newCacheSystem, $newMemcachedDsn, $newRedisDsn, $newCacheLabel, $preMessage);
        }
    }

    /**
     * Clear old cache if necessary
     *
     * @param bool $cacheChanged
     * @param string $oldCacheSystem
     * @param string $oldMemcachedDsn
     * @param string $oldRedisDsn
     * @param Phrase $oldCacheLabel
     * @throws FileSystemException
     * @throws InvalidArgumentException
     */
    protected function _handleOldClearCache(
        bool   $cacheChanged,
        string $oldCacheSystem,
        string $oldMemcachedDsn,
        string $oldRedisDsn,
        Phrase $oldCacheLabel
    ) {
        if ($cacheChanged) {
            $this->_clearCache($oldCacheSystem, $oldMemcachedDsn, $oldRedisDsn, $oldCacheLabel);
        }
    }

    /**
     * Test a cache configuration for some bouncer
     *
     * @param bool $cacheChanged
     * @param string $cacheSystem
     * @param string $memcachedDsn
     * @param string $redisDsn
     * @param Phrase $cacheLabel
     * @throws CrowdSecException|InvalidArgumentException
     */
    protected function _handleTestCache(
        bool   $cacheChanged,
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
                $configs = $this->helper->getBouncerConfigs();
                $bouncer->init(
                    $configs,
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
                    "Technical error while testing the $cacheLabel cache: " . $e->getMessage();
                throw new CrowdSecException($cacheMessage);
            }
        }
    }

    /**
     * Clear a cache for some config and bouncer
     *
     * @param string $cacheSystem
     * @param string $memcachedDsn
     * @param string $redisDsn
     * @param Phrase $cacheLabel
     * @param Phrase|null $preMessage
     * @throws InvalidArgumentException
     * @throws FileSystemException
     */
    protected function _clearCache(
        string  $cacheSystem,
        string  $memcachedDsn,
        string  $redisDsn,
        Phrase  $cacheLabel,
        Phrase $preMessage = null
    ): void {
        try {
            if (!($bouncer = $this->registryBouncer->get())) {
                $bouncer = $this->registryBouncer->create();
            }
            $configs = $this->helper->getBouncerConfigs();
            $clearCacheResult =
                $bouncer->init(
                    $configs,
                    [
                        'forced_cache_system' => $cacheSystem,
                        'memcached_dsn' => $memcachedDsn,
                        'redis_dsn' => $redisDsn
                    ]
                )->clearCache();
            $this->displayCacheClearMessage($clearCacheResult, $cacheLabel, $preMessage);
        } catch (CrowdSecException $e) {
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
     * Warm up the cache
     *
     * @param string $cacheSystem
     * @param string $memcachedDsn
     * @param string $redisDsn
     * @param Phrase $cacheLabel
     * @return void
     * @throws FileSystemException
     * @throws InvalidArgumentException
     */
    protected function _warmUpCache(
        string $cacheSystem,
        string $memcachedDsn,
        string $redisDsn,
        Phrase $cacheLabel
    ): void {
        try {
            if (!($bouncer = $this->registryBouncer->get())) {
                $bouncer = $this->registryBouncer->create();
            }
            $configs = $this->helper->getBouncerConfigs();
            $warmUpCacheResult =
                $bouncer->init(
                    $configs,
                    [
                        'forced_cache_system' => $cacheSystem,
                        'memcached_dsn' => $memcachedDsn,
                        'redis_dsn' => $redisDsn
                    ]
                )->warmBlocklistCacheUp();
            $this->displayCacheWarmUpMessage($warmUpCacheResult, $cacheLabel);
        } catch (CrowdSecException $e) {
            $this->helper->error('', [
                'type' => 'M2_EXCEPTION_WHILE_WARMING_UP_CACHE',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $cacheMessage =
                "Technical error while warming up the $cacheLabel cache: " . $e->getMessage();
            throw new CrowdSecException($cacheMessage);
        }
    }

    /**
     * Check if DNS configuration has changed
     *
     * @param string $newCacheSystem
     * @param string $oldRedisDsn
     * @param string $newRedisDsn
     * @param string $oldMemcachedDsn
     * @param string $newMemcachedDsn
     * @return bool
     */
    private function hasDsnChanged(
        string $newCacheSystem,
        string $oldRedisDsn,
        string $newRedisDsn,
        string $oldMemcachedDsn,
        string $newMemcachedDsn
    ): bool {
        switch ($newCacheSystem) {
            case Constants::CACHE_SYSTEM_REDIS:
                return $oldRedisDsn !== $newRedisDsn;
            case Constants::CACHE_SYSTEM_MEMCACHED:
                return $oldMemcachedDsn !== $newMemcachedDsn;
            default:
                return false;
        }
    }

    /**
     * Manage cache clear message display
     *
     * @param bool $clearCacheResult
     * @param Phrase $cacheLabel
     * @param Phrase|null $preMessage
     * @return void
     */
    private function displayCacheClearMessage(
        bool $clearCacheResult,
        Phrase $cacheLabel,
        Phrase $preMessage = null
    ): void {
        $clearCacheMessage =
            $clearCacheResult ? __('%1 cache has been cleared.', $cacheLabel) :
                __('%1 cache has not been cleared.', $cacheLabel);
        $this->messageManager->addNoticeMessage($preMessage . $clearCacheMessage);
    }

    /**
     * Manage cache warm up message display
     *
     * @param array $warmUpCacheResult
     * @param Phrase $cacheLabel
     * @return void
     */
    private function displayCacheWarmUpMessage(array $warmUpCacheResult, Phrase $cacheLabel): void
    {
        $decisionsCount = $warmUpCacheResult['count'] ?? 0;
        $decisionsMessage =
            $decisionsCount > 1 ? 'There are now %1 decisions in cache.' : 'There is now %1 decision in cache.';
        $message = __('As the stream mode is enabled, the cache (%1) has been warmed up.', $cacheLabel);
        $message .= ' ' . __("$decisionsMessage", $decisionsCount);

        $this->messageManager->addNoticeMessage($message);
    }
}
