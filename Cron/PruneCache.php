<?php declare(strict_types=1);

namespace CrowdSec\Bouncer\Cron;

use CrowdSec\Bouncer\Constants;
use CrowdSec\Bouncer\Helper\Data as Helper;
use CrowdSec\Bouncer\Registry\CurrentBounce as RegistryBounce;
use Exception;
use LogicException;

class PruneCache
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
     *  Prune file system cache
     *
     * @return void
     * @throws LogicException
     */
    public function execute(): void
    {
        if ($this->helper->getCacheTechnology() === Constants::CACHE_SYSTEM_PHPFS) {
            try {
                $bounce = $this->registryBounce->create();
                $configs = $this->helper->getBouncerConfigs();
                $bounce->init($configs)->pruneCache();
            } catch (Exception $e) {
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
