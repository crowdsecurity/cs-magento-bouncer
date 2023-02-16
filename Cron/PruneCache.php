<?php declare(strict_types=1);

namespace CrowdSec\Bouncer\Cron;

use CrowdSec\Bouncer\Constants;
use CrowdSec\Bouncer\Helper\Data as Helper;
use CrowdSec\Bouncer\Registry\CurrentBouncer as RegistryBouncer;
use Exception;
use LogicException;

class PruneCache
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
     *  Prune file system cache
     *
     * @return void
     * @throws LogicException
     */
    public function execute(): void
    {
        if ($this->helper->getCacheTechnology() === Constants::CACHE_SYSTEM_PHPFS) {
            try {
                $configs = $this->helper->getBouncerConfigs();
                $bouncer = $this->registryBouncer->create([
                    'helper' => $this->helper,
                    'configs' => $configs
                ]);
                $result = $bouncer->pruneCache();
                if ($result) {
                    $this->helper->info('Cache has been pruned by cron', []);
                }
            } catch (Exception $e) {
                $this->helper->error('Error while pruning cache', [
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
