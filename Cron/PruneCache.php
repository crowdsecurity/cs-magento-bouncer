<?php declare(strict_types=1);

namespace CrowdSec\Bouncer\Cron;

use CrowdSec\Bouncer\Constants;
use CrowdSec\Bouncer\Exception\CrowdSecException;
use CrowdSec\Bouncer\Helper\Data as Helper;
use CrowdSec\Bouncer\Registry\CurrentBouncer as RegistryBouncer;

class PruneCache
{
    /**
     * @var Helper
     */
    protected $helper;
    /**
     */
    protected $registryBouncer;

    public function __construct(Helper $helper, RegistryBouncer $registryBouncer)
    {
        $this->helper = $helper;
        $this->registryBouncer = $registryBouncer;
    }

    /**
     * Prune file system cache
     *
     */
    public function execute(): void
    {
        if ($this->helper->getCacheTechnology() === Constants::CACHE_SYSTEM_PHPFS) {
            try {
                $bouncer = $this->registryBouncer->create();
                $configs = $this->helper->getBouncerConfigs();
                $bouncer->init($configs)->pruneCache();
            } catch (CrowdSecException $e) {
                $this->helper->error('', [
                    'type' => 'M2_EXCEPTION_WHILE_PRUNING_CACHE',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
        }
    }
}
