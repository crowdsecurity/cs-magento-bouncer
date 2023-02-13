<?php declare(strict_types=1);

namespace CrowdSec\Bouncer\Cron;

use CrowdSec\Bouncer\Helper\Data as Helper;
use CrowdSec\Bouncer\Registry\CurrentBouncer as RegistryBouncer;
use Exception;
use LogicException;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;

class RefreshCache
{
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
     * @param Helper $helper
     * @param RegistryBouncer $registryBouncer
     */
    public function __construct(Helper $helper, RegistryBouncer $registryBouncer)
    {
        $this->helper = $helper;
        $this->registryBouncer = $registryBouncer;
    }

    /**
     * Refresh cache in Stream Mode
     *
     * @return void
     * @throws LogicException
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    public function execute(): void
    {
        if ($this->helper->isStreamModeEnabled()) {
            try {
                $configs = $this->helper->getBouncerConfigs();
                $bouncer = $this->registryBouncer->create([
                    'helper' => $this->helper,
                    'configs' => $configs
                ]);

                $bouncer->refreshBlocklistCache();
                $this->helper->info('Cache has been refreshed by cron', []);
            } catch (Exception $e) {
                $this->helper->error('Error while refreshing cache', [
                    'type' => 'M2_EXCEPTION_WHILE_REFRESHING_CACHE',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
        }
    }
}
