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

    public function __construct(BouncerFactory $bouncerFactory)
    {
        $this->bouncerFactory = $bouncerFactory;
    }

    public function set(BouncerModel $bouncer): void
    {
        $this->bouncer = $bouncer;
    }

    public function get(): ?BouncerModel
    {
        return $this->bouncer;
    }

    public function create(array $data = []): BouncerModel
    {
        $bouncer =  $this->bouncerFactory->create($data);
        $this->set($bouncer);
        return $bouncer;
    }
}
