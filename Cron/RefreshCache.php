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
     */
    protected $registryBouncer;

    public function __construct(Helper $helper, RegistryBouncer $registryBouncer)
    {

        $this->helper = $helper;
        $this->registryBouncer = $registryBouncer;
    }

    /**
     * Refresh cache in Stream Mode
     *
     */
    public function execute(): void
    {
        if ($this->helper->isStreamModeEnabled()) {
            try {
                $bouncer = $this->registryBouncer->create();
                $bouncer->init()->refreshBlocklistCache();
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
