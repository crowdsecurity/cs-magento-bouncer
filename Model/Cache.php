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

namespace Crowdsec\Bouncer\Model;

use ErrorException;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Exception\CacheException;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Crowdsec\Bouncer\Exception\CrowdsecException;
use Crowdsec\Bouncer\Constants;

class Cache
{

    /** @var array */
    private $cacheAdapter = [];

    /**
     * Initialize cache adapter for Crowdsec Bouncer
     * @param string $cacheSystem
     * @param string $memcachedDsn
     * @param string $redisDsn
     * @param string $fsCachePath
     * @param string|null $forcedCacheSystem
     * @return mixed
     * @throws CrowdsecException
     * @throws ErrorException|CacheException
     */
    public function getAdapter(
        string $cacheSystem,
        string $memcachedDsn,
        string $redisDsn,
        string $fsCachePath,
        string $forcedCacheSystem = null
    ) {
        if (!isset($this->cacheAdapter[$cacheSystem][$memcachedDsn][$redisDsn][$fsCachePath][$forcedCacheSystem])) {
            $cacheSystem = $forcedCacheSystem ?: $cacheSystem;
            switch ($cacheSystem) {
                case Constants::CACHE_SYSTEM_PHPFS:
                    $cacheAdapterInstance = new PhpFilesAdapter('', 0, $fsCachePath);
                    break;

                case Constants::CACHE_SYSTEM_MEMCACHED:
                    if (empty($memcachedDsn)) {
                        throw new CrowdsecException(
                            __('The selected cache technology is Memcached.' .
                               ' Please set a Memcached DSN or select another cache technology.')
                        );
                    }
                    $cacheAdapterInstance = new MemcachedAdapter(MemcachedAdapter::createConnection($memcachedDsn));
                    break;
                case Constants::CACHE_SYSTEM_REDIS:
                    if (empty($redisDsn)) {
                        throw new CrowdsecException(__('The selected cache technology is Redis.' .
                                                       ' Please set a Redis DSN or select another cache technology.'));
                    }

                    try {
                        $cacheAdapterInstance = new RedisAdapter(RedisAdapter::createConnection($redisDsn));
                    } catch (InvalidArgumentException $e) {
                        throw new CrowdsecException(
                            __('Error when connecting to Redis.' .
                               ' Please fix the Redis DSN or select another cache technology.')
                        );
                    }

                    break;
                default:
                    throw new CrowdsecException(__('Unknown selected cache technology.'));
            }
            $this->cacheAdapter[$cacheSystem][$memcachedDsn][$redisDsn][$fsCachePath][$forcedCacheSystem] =
                $cacheAdapterInstance;
        }

        return $this->cacheAdapter[$cacheSystem][$memcachedDsn][$redisDsn][$fsCachePath][$forcedCacheSystem];
    }
}
