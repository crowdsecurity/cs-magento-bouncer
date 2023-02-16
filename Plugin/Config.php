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

use CrowdSec\Bouncer\Helper\Data as Helper;
use CrowdSec\Bouncer\Registry\CurrentBouncer as RegistryBouncer;
use CrowdSecBouncer\BouncerException;
use Exception;
use LogicException;
use Magento\Framework\Message\ManagerInterface;
use CrowdSec\Bouncer\Constants;
use Magento\Framework\Phrase;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Psr\Cache\CacheException;
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
     */
    public function __construct(
        ManagerInterface $messageManager,
        Helper           $helper,
        RegistryBouncer   $registryBouncer,
        WriterInterface  $configWriter
    ) {
        $this->messageManager = $messageManager;
        $this->helper = $helper;
        $this->registryBouncer = $registryBouncer;
        $this->configWriter = $configWriter;
    }

    /**
     * Retrieve old and new TLS settings
     *
     * @param MagentoConfig $subject
     * @param boolean $isTLS
     * @return array[]
     * @throws BouncerException
     */
    protected function _getTLS($subject, $isTLS)
    {
        $result = ['old'=>[], 'new' => []];
        if ($isTLS) {
            $oldTls = $this->helper->getTLS();
            $oldTlsCert = $oldTls['tls_cert_path'];
            $newTlsCert = ($subject->getData(Helper::API_TLS_CERT_FULL_PATH)) ?
                $this->helper->getVarFullPath($subject->getData(Helper::API_TLS_CERT_FULL_PATH)) : $oldTlsCert;

            $oldTlsKey = $oldTls['tls_key_path'];
            $newTlsKey = ($subject->getData(Helper::API_TLS_KEY_FULL_PATH)) ?
                $this->helper->getVarFullPath($subject->getData(Helper::API_TLS_KEY_FULL_PATH)) : $oldTlsKey;

            $oldTlsVerify = $oldTls['tls_verify_peer'];
            $newTlsVerify = ($subject->getData(Helper::API_TLS_VERIFY_FULL_PATH) === null)
                ? $oldTlsVerify
                : (bool)$subject->getData(Helper::API_TLS_VERIFY_FULL_PATH);

            $oldTlsCaCert = $oldTls['tls_ca_cert_path'];
            $newTlsCaCert = ($subject->getData(Helper::API_TLS_CA_CERT_FULL_PATH)) ?
                $this->helper->getVarFullPath($subject->getData(Helper::API_TLS_CA_CERT_FULL_PATH)) : $oldTlsCaCert;

            $result['new'] = [
                'tls_cert_path' => $newTlsCert,
                'tls_key_path' => $newTlsKey,
                'tls_verify_peer' => $newTlsVerify,
                'tls_ca_cert_path' => $newTlsCaCert
            ];

            $result['old'] = $oldTls;

        }

        return $result;
    }

    /**
     * Retrieve old and new connections settings
     *
     * @param MagentoConfig $subject
     * @return array[]
     * @throws BouncerException
     */
    protected function _getConnections($subject)
    {
        $oldUrl = $this->helper->getApiUrl();
        $newUrl = $this->getCurrentValue($subject->getData(Helper::API_URL_FULL_PATH), $oldUrl);
        $oldAuthType = $this->helper->getApiAuthType();
        $newAuthType = $this->getCurrentValue($subject->getData(Helper::API_AUTH_TYPE_FULL_PATH), $oldAuthType);
        $isTLS = $newAuthType === Constants::AUTH_TLS;
        $tls = $this->_getTLS($subject, $isTLS);

        $oldKey = $this->helper->getApiKey();
        $newKey = $this->getCurrentValue($subject->getData(Helper::API_KEY_FULL_PATH), $oldKey);

        $oldUseCurl = $this->helper->isUseCurl();
        $newUseCurl = ($subject->getData(Helper::API_USE_CURL_FULL_PATH) === null)
            ? $oldUseCurl
            : (bool)$subject->getData(Helper::API_USE_CURL_FULL_PATH);

        $oldConnexion = [
            'api_url' => $oldUrl,
            'auth_type' => $oldAuthType,
            'use_curl' => $oldUseCurl,
            'api_key' => $oldKey,
            'tls' => $tls['old']
        ];
        $newConnexion = [
            'api_url' => $newUrl,
            'auth_type' => $newAuthType,
            'use_curl' => $newUseCurl,
            'api_key' => $isTLS ? "" : $newKey,
            'tls' => $tls['new']
        ];

        return ['old' => $oldConnexion, 'new' => $newConnexion];
    }

    /**
     * Handle admin CrowdSec section changes
     *
     * @param MagentoConfig $subject
     * @return null
     * @throws LogicException|BouncerException|CacheException
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

            $connections = $this->_getConnections($subject);

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
            $this->_handleConnectionChanges($connections['old'], $connections['new']);
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
     * @throws LogicException
     * @throws BouncerException|CacheException
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
        $this->_handleRefresh(
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
     * @throws BouncerException
     * @throws LogicException
     * @throws CacheException
     */
    protected function _handleRefresh(
        bool   $oldStreamMode,
        bool   $newStreamMode,
        bool   $refreshCronExprChanged,
        bool   $cacheChanged,
        string $newCacheSystem,
        string $newRedisDsn,
        string $newMemcachedDsn,
        Phrase $newCacheLabel
    ) {
        $shouldRefresh = false;
        if ($newStreamMode === true) {
            if ($oldStreamMode !== true) {
                $shouldRefresh = true;
            }
            if ($refreshCronExprChanged) {
                $shouldRefresh = true;
            }
            if ($cacheChanged) {
                $shouldRefresh = true;
            }
        }

        if ($shouldRefresh) {
            $this->_refreshCache($newCacheSystem, $newMemcachedDsn, $newRedisDsn, $newCacheLabel);
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
     * @throws BouncerException
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
                throw new BouncerException('Disabled refresh cron expression can\'t be saved: ' . $e->getMessage());
            }
        } elseif ($cronExprChanged) {
            // Check expression
            try {
                $this->helper->validateCronExpr($newCronExpr);
            } catch (Exception $e) {
                $this->messageManager->getMessages(true);
                throw new BouncerException("Refresh cron expression ($newCronExpr) is not valid: ". $e->getMessage());
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
     * @throws BouncerException
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
                throw new BouncerException('Disabled pruning cron expression can\'t be saved: ' . $e->getMessage());
            }
        } elseif ($cronExprChanged) {
            // Check expression
            try {
                $this->helper->validateCronExpr($newCronExpr);
            } catch (Exception $e) {
                $this->messageManager->getMessages(true);
                throw new BouncerException("Pruning cron expression ($newCronExpr) is not valid: ". $e->getMessage());
            }
        }
    }

    /**
     * Handle connection changes
     *
     * @param array $oldConnection
     * @param array $newConnection
     * @return void
     * @throws BouncerException
     */
    protected function _handleConnectionChanges(
        array $oldConnection,
        array $newConnection
    ) {
        // Test connection if params changed
        if ($oldConnection != $newConnection && !empty($newConnection['api_url'])) {
            $finalApiKey = $newConnection['api_key']??"";
            $finalCert = $newConnection['tls']['tls_cert_path']??"";
            $finalKey = $newConnection['tls']['tls_key_path']??"" ;
            $finalVerify = $newConnection['tls']['tls_verify_peer']??false;
            $finalCaCert = $newConnection['tls']['tls_ca_cert_path']??"";
            $finalUseCurl = $newConnection['use_curl']??false;
            try {
                $configs = $this->helper->getBouncerConfigs();
                $currentConfigs = [
                    'api_url' => $newConnection['api_url'],
                    'api_key' => $finalApiKey,
                    'use_curl' => $newConnection['use_curl'],
                    'auth_type' => $newConnection['auth_type'],
                    'tls_cert_path' => $finalCert,
                    'tls_key_path' => $finalKey,
                    'tls_ca_cert_path' => $finalCaCert,
                    'tls_verify_peer' => $finalVerify,
                ];
                $bouncer = $this->registryBouncer->create([
                    'helper' => $this->helper,
                    'configs' => array_merge($configs, $currentConfigs)
                ]);

                $restClient = $bouncer->getRemediationEngine()->getClient();

                $this->helper->ping($restClient);
            } catch (Exception $e) {
                $message = 'Connection test failed with <br>auth_type=' . $newConnection['auth_type']
                           . '<br>url=' . $newConnection['api_url']
                           . '<br>use curl=' . (!empty($finalUseCurl) ? 'true' : 'false')
                           . '<br>api key=' . ($finalApiKey ?? "")
                           . '<br>tls cert path=' . ($finalCert ?? "")
                           . '<br>tls key path=' . ($finalKey ?? "")
                           . '<br>tls ca cert path=' . ($finalCaCert?? "")
                           . '<br>tls verify peer=' . (!empty($finalVerify) ? 'true' : 'false')
                           . '<br>: ';
                throw new BouncerException($message . $e->getMessage());
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
     * @throws LogicException
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
            if ($oldStreamMode !== false) {
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
     * @throws InvalidArgumentException
     * @throws LogicException
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
     * @throws InvalidArgumentException|LogicException|BouncerException
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
                $configs = $this->helper->getBouncerConfigs();
                $currentConfigs = [
                    'cache_system' => $cacheSystem,
                    'memcached_dsn' => $memcachedDsn,
                    'redis_dsn' => $redisDsn
                ];
                $bouncer = $this->registryBouncer->create([
                    'helper' => $this->helper,
                    'configs' => array_merge($configs, $currentConfigs)
                ]);

                $bouncer->testCacheConnection();
                $cacheMessage = __('CrowdSec new cache (%1) has been successfully tested.', $cacheLabel);
                $this->messageManager->addNoticeMessage($cacheMessage);
            } catch (Exception $e) {
                $this->helper->error('Error while testing cache', [
                    'type' => 'M2_EXCEPTION_WHILE_TESTING_CACHE',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                $cacheMessage =
                    "Technical error while testing the $cacheLabel cache: " . $e->getMessage();
                throw new BouncerException($cacheMessage);
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
     * @throws LogicException
     */
    protected function _clearCache(
        string $cacheSystem,
        string $memcachedDsn,
        string $redisDsn,
        Phrase $cacheLabel,
        Phrase $preMessage = null
    ): void {
        try {
            $configs = $this->helper->getBouncerConfigs();
            $currentConfigs = [
                'cache_system' => $cacheSystem,
                'memcached_dsn' => $memcachedDsn,
                'redis_dsn' => $redisDsn
            ];
            $bouncer = $this->registryBouncer->create([
                'helper' => $this->helper,
                'configs' => array_merge($configs, $currentConfigs)
            ]);

            $clearCacheResult = $bouncer->clearCache();
            $this->displayCacheClearMessage($clearCacheResult, $cacheLabel, $preMessage);
        } catch (Exception $e) {
            $this->helper->error('Error while clearing cache', [
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
     * @throws BouncerException
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws CacheException
     */
    protected function _refreshCache(
        string $cacheSystem,
        string $memcachedDsn,
        string $redisDsn,
        Phrase $cacheLabel
    ): void {
        try {
            $configs = $this->helper->getBouncerConfigs();
            $currentConfigs = [
                'cache_system' => $cacheSystem,
                'memcached_dsn' => $memcachedDsn,
                'redis_dsn' => $redisDsn
            ];
            $bouncer = $this->registryBouncer->create([
                'helper' => $this->helper,
                'configs' => array_merge($configs, $currentConfigs)
            ]);

            $refreshResult = $bouncer->refreshBlocklistCache();
            $this->displayCacheRefreshMessage($refreshResult, $cacheLabel);
        } catch (Exception $e) {
            $this->helper->error('Error while refreshing cache', [
                'type' => 'M2_EXCEPTION_WHILE_REFRESHING_CACHE',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $cacheMessage =
                "Technical error while warming up the $cacheLabel cache: " . $e->getMessage();
            throw new BouncerException($cacheMessage);
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
        bool   $clearCacheResult,
        Phrase $cacheLabel,
        Phrase $preMessage = null
    ): void {
        $clearCacheMessage =
            $clearCacheResult ? __('%1 cache has been cleared.', $cacheLabel) :
                __('%1 cache has not been cleared.', $cacheLabel);
        $this->messageManager->addNoticeMessage($preMessage . $clearCacheMessage);
    }

    /**
     * Manage cache refresh message display
     *
     * @param array $refreshCacheResult
     * @param Phrase $cacheLabel
     * @return void
     */
    private function displayCacheRefreshMessage(array $refreshCacheResult, Phrase $cacheLabel): void
    {
        $newDecisionsCount = $refreshCacheResult['new'] ?? 0;
        $deletedDecisionsCount = $refreshCacheResult['deleted'] ?? 0;
        $decisionsMessage = 'New decision(s): %1. Deleted decision(s): %2. ';
        $message = __('As the stream mode is enabled, the cache (%1) has been refreshed.', $cacheLabel);
        $message .= ' ' . __("$decisionsMessage", $newDecisionsCount, $deletedDecisionsCount);

        $this->messageManager->addNoticeMessage($message);
    }
}
