<?php /** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUndefinedClassInspection */
declare(strict_types=1);

namespace CrowdSec\Bouncer\Registry;

use CrowdSec\Bouncer\Model\Bounce as BounceModel;
use CrowdSec\Bouncer\Model\BounceFactory;

/**
 * Class CurrentBounce
 * As the Magento\Framework\Registry is deprecated since Magento 2.3, we use this custom local registry
 * @see https://github.com/Vinai/module-current-product-example#what-are-the-benefits-of-a-custom-registry-object-over-the-core-registry
 */
class CurrentBounce
{
    /**
     * @var BounceModel
     */
    private $bounce;

    /**
     * @var BounceFactory
     */
    private $bounceFactory;

    /**
     * Constructor
     *
     * @param BounceFactory $bounceFactory
     */
    public function __construct(BounceFactory $bounceFactory)
    {
        $this->bounceFactory = $bounceFactory;
    }

    /**
     * Set a bounce as a registry bounce
     *
     * @param BounceModel $bounce
     * @return void
     */
    public function set(BounceModel $bounce): void
    {
        $this->bounce = $bounce;
    }

    /**
     * Retrieve the registered bounce if any
     *
     * @return BounceModel|null
     */
    public function get(): ?BounceModel
    {
        return $this->bounce;
    }

    /**
     * Create a bounce and set the result as the current registered bounce
     *
     * @param array $data
     * @return BounceModel
     */
    public function create(array $data = []): BounceModel
    {
        $bounce =  $this->bounceFactory->create($data);
        $this->set($bounce);
        return $bounce;
    }
}
