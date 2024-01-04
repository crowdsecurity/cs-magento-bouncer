<?php

declare(strict_types=1);

namespace CrowdSec\Bouncer\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use CrowdSec\Bouncer\Helper\Config;

class EncryptBouncerKey implements DataPatchInterface
{
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * Constructor method.
     *
     * @param EncryptorInterface $encryptor
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        EncryptorInterface $encryptor,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->encryptor = $encryptor;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Apply patch.
     *
     * @return void
     */
    public function apply()
    {
        $bouncerKeyPath = Config::XML_PATH_API_KEY;
        $configTable = $this->moduleDataSetup->getTable('core_config_data');
        $select = $this->moduleDataSetup->getConnection()->select()
            ->from($configTable)
            ->where('path = ?', $bouncerKeyPath);
        $config = $this->moduleDataSetup->getConnection()->fetchAll($select);
        if (!empty($config)) {
            $value = $config[0]['value'] ?? '';
            if ($value) {
                $this->moduleDataSetup->getConnection()->update(
                    $configTable,
                    ['value' => $this->encryptor->encrypt($value)],
                    ['path = ?' => $bouncerKeyPath]
                );
            }
        }
    }

    /**
     * Retrieve dependencies.
     *
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Retrieve aliases
     *
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }
}
