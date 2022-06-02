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

namespace CrowdSec\Bouncer\Model;

use ErrorException;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Exception\CacheException;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use CrowdSec\Bouncer\Exception\CrowdSecException;
use CrowdSec\Bouncer\Constants;

class Cache
{

    /** @var array */
    private $cacheAdapter = [];

    /**
     * Initialize cache adapter for CrowdSec Bouncer
     *
     * @param string $cacheSystem
     * @param string $memcachedDsn
     * @param string $redisDsn
     * @param string $fsCachePath
     * @param string|null $forcedCacheSystem
     * @return mixed
     * @throws CacheException
     * @throws ErrorException
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
                    $cacheAdapterInstance = new TagAwareAdapter(new PhpFilesAdapter('', 0, $fsCachePath));
                    break;

                case Constants::CACHE_SYSTEM_MEMCACHED:
                    if (empty($memcachedDsn)) {
                        throw new CrowdSecException(
                            'The selected cache technology is Memcached.' .
                               ' Please set a Memcached DSN or select another cache technology.'
                        );
                    }
                    $cacheAdapterInstance = new TagAwareAdapter(
                        new MemcachedAdapter(MemcachedAdapter::createConnection($memcachedDsn))
                    );
                    break;
                case Constants::CACHE_SYSTEM_REDIS:
                    if (empty($redisDsn)) {
                        throw new CrowdSecException('The selected cache technology is Redis.' .
                                                       ' Please set a Redis DSN or select another cache technology.');
                    }

                    try {
                        $cacheAdapterInstance = new RedisTagAwareAdapter(RedisAdapter::createConnection($redisDsn));
                    } catch (InvalidArgumentException $e) {
                        throw new CrowdSecException('Error when connecting to Redis.' .
                               ' Please fix the Redis DSN or select another cache technology.');
                    }

                    break;
                default:
                    throw new CrowdSecException('Unknown selected cache technology.');
            }
            $this->cacheAdapter[$cacheSystem][$memcachedDsn][$redisDsn][$fsCachePath][$forcedCacheSystem] =
                $cacheAdapterInstance;
        }

        return $this->cacheAdapter[$cacheSystem][$memcachedDsn][$redisDsn][$fsCachePath][$forcedCacheSystem];
    }
}
