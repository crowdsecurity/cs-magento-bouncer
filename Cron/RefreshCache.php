<?php declare(strict_types=1);

namespace Crowdsec\Bouncer\Cron;

use Crowdsec\Bouncer\Exception\CrowdsecException;
use Crowdsec\Bouncer\Helper\Data as Helper;
use Crowdsec\Bouncer\Registry\CurrentBouncer as RegistryBouncer;

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
    public function refreshCache(): void
    {
        if ($this->helper->isStreamModeEnabled()) {
            try {
                $bouncer = $this->registryBouncer->create();
                $bouncer->init()->refreshBlocklistCache();
            } catch (CrowdsecException $e) {
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
