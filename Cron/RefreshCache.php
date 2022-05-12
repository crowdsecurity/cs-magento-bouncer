<?php declare(strict_types=1);

namespace CrowdSec\Bouncer\Cron;

use CrowdSec\Bouncer\Exception\CrowdSecException;
use CrowdSec\Bouncer\Helper\Data as Helper;
use CrowdSec\Bouncer\Registry\CurrentBouncer as RegistryBouncer;

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
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function execute(): void
    {
        if ($this->helper->isStreamModeEnabled()) {
            try {
                $bouncer = $this->registryBouncer->create();
                $configs = $this->helper->getBouncerConfigs();
                $bouncer->init($configs)->refreshBlocklistCache();
            } catch (CrowdSecException $e) {
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
