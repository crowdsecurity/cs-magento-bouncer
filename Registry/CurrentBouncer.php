<?php /** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUndefinedClassInspection */
declare(strict_types=1);

namespace CrowdSec\Bouncer\Registry;

use CrowdSec\Bouncer\Model\Bouncer as BouncerModel;
use CrowdSec\Bouncer\Model\BouncerFactory;

/**
 * Class CurrentBouncer
 * As the Magento\Framework\Registry is deprecated since Magento 2.3, we use this custom local registry
 * @see https://github.com/Vinai/module-current-product-example#what-are-the-benefits-of-a-custom-registry-object-over-the-core-registry
 */
class CurrentBouncer
{
    /**
     * @var BouncerModel
     */
    private $bouncer;

    /**
     * @var BouncerFactory
     */
    private $bouncerFactory;

    /**
     * Constructor
     *
     * @param BouncerFactory $bouncerFactory
     */
    public function __construct(BouncerFactory $bouncerFactory)
    {
        $this->bouncerFactory = $bouncerFactory;
    }

    /**
     * Set a bounce as a registry bouncer
     *
     * @param BouncerModel $bouncer
     * @return void
     */
    public function set(BouncerModel $bouncer): void
    {
        $this->bouncer = $bouncer;
    }

    /**
     * Retrieve the registered bouncer if any
     *
     * @return BouncerModel|null
     */
    public function get(): ?BouncerModel
    {
        return $this->bouncer;
    }

    /**
     * Create a bouncer and set the result as the current registered bouncer
     *
     * @param array $data
     * @return BouncerModel
     */
    public function create(array $data = []): BouncerModel
    {
        $bouncer =  $this->bouncerFactory->create($data);
        $this->set($bouncer);
        return $bouncer;
    }
}
