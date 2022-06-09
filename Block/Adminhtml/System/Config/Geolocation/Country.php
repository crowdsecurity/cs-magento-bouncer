<?php declare(strict_types=1);
/**
 * CrowdSec_Bouncer Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT LICENSE
 * that is bundled with this package in the file LICENSE
 *
 * @category   CrowdSec
 * @package    CrowdSec_Bouncer
 * @copyright  Copyright (c)  2021+ CrowdSec
 * @author     CrowdSec team
 * @see        https://crowdsec.net CrowdSec Official Website
 * @license    MIT LICENSE
 *
 */

/**
 *
 * @category CrowdSec
 * @package  CrowdSec_Bouncer
 * @module   Bouncer
 * @author   CrowdSec team
 *
 */

namespace CrowdSec\Bouncer\Block\Adminhtml\System\Config\Geolocation;

use CrowdSec\Bouncer\Block\Adminhtml\System\Config\Button;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Country extends Button
{

    /**
     * Geolocation type field Name
     *
     * @var string
     */
    protected $_geolocationTypeField = 'crowdsec_bouncer_advanced_geolocation_type';

    /**
     * Geolocation MaxMind database type field name
     *
     * @var string
     */
    protected $_geolocationMaxmindDatabaseTypeField = 'crowdsec_bouncer_advanced_geolocation_maxmind_database_type';

    /**
     * Geolocation MaxMind database path field name
     *
     * @var string
     */
    protected $_geolocationMaxmindDatabasePathField = 'crowdsec_bouncer_advanced_geolocation_maxmind_database_path';

    /**
     * Forced test IP field name
     *
     * @var string
     */
    protected $_forcedTestIpField = 'crowdsec_bouncer_advanced_debug_forced_test_ip';

    /**
     * Save result field name
     *
     * @var string
     */
    protected $_saveResultField = 'crowdsec_bouncer_advanced_geolocation_save_result';

    /** @var string  */
    protected $template = 'CrowdSec_Bouncer::system/config/geolocation/country.phtml';
    /** @var string  */
    protected $oldTemplate = 'CrowdSec_Bouncer::system/config/geolocation/old/country.phtml';

    /**
     * Geolocation type field Name
     *
     * @return string
     */
    public function getGeolocationTypeField(): string
    {
        return $this->_geolocationTypeField;
    }

    /**
     * Geolocation MaxMind database type field name
     *
     * @return string
     */
    public function getGeolocationMaxmindDatabaseTypeField(): string
    {
        return $this->_geolocationMaxmindDatabaseTypeField;
    }

    /**
     * Geolocation MaxMind database path field name
     *
     * @return string
     */
    public function getGeolocationMaxmindDatabasePathField(): string
    {
        return $this->_geolocationMaxmindDatabasePathField;
    }

    /**
     * Forced test IP field name
     *
     * @return string
     */
    public function getForcedTestIpField(): string
    {
        return $this->_forcedTestIpField;
    }

    /**
     * Save result field name
     *
     * @return string
     */
    public function getSaveResultField(): string
    {
        return $this->_saveResultField;
    }

    /**
     * Get the button and scripts contents
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $buttonLabel =  __('Test geolocation settings');
        $this->addData(
            [
                'button_label' => $buttonLabel,
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $this->_urlBuilder->getUrl('crowdsec/system_config_geolocation/country'),
            ]
        );

        return $this->_toHtml();
    }
}
