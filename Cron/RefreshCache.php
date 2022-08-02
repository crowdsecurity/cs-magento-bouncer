<?php declare(strict_types=1);

namespace CrowdSec\Bouncer\Cron;

use CrowdSec\Bouncer\Helper\Data as Helper;
use CrowdSec\Bouncer\Registry\CurrentBounce as RegistryBounce;
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
     * @var RegistryBounce
     */
    protected $registryBounce;

    /**
     * Constructor
     *
     * @param Helper $helper
     * @param RegistryBounce $registryBounce
     */
    public function __construct(Helper $helper, RegistryBounce $registryBounce)
    {
        $this->helper = $helper;
        $this->registryBounce = $registryBounce;
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
                $bounce = $this->registryBounce->create();
                $configs = $this->helper->getBouncerConfigs();
                $bounce->init($configs)->refreshBlocklistCache();
            } catch (Exception $e) {
                $this->helper->error('', [
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
