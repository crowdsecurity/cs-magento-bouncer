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

namespace CrowdSec\Bouncer\Block\Adminhtml\System\Config\Connection;

use CrowdSec\Bouncer\Block\Adminhtml\System\Config\Button;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Ping extends Button
{

    /**
     * LAPI Url field Name
     *
     * @var string
     */
    protected $_apiUrlField = 'crowdsec_bouncer_general_connection_api_url';

    /**
     * Bouncer key field name
     *
     * @var string
     */
    protected $_bouncerKeyField = 'crowdsec_bouncer_general_connection_api_key';
    /**
     * Use curl field name
     *
     * @var string
     */
    protected $_useCurlField = 'crowdsec_bouncer_general_connection_use_curl';

    /** @var string  */
    protected $template = 'CrowdSec_Bouncer::system/config/connection/ping.phtml';
    /** @var string  */
    protected $oldTemplate = 'CrowdSec_Bouncer::system/config/connection/old/ping.phtml';

    /**
     * Get LAPI Url field Name
     *
     * @return string
     */
    public function getUrlField(): string
    {
        return $this->_apiUrlField;
    }

    /**
     * Get Bouncer key field Name
     *
     * @return string
     */
    public function getKeyField(): string
    {
        return $this->_bouncerKeyField;
    }

    /**
     * Get use curl field Name
     *
     * @return string
     */
    public function getUseCurlField(): string
    {
        return $this->_useCurlField;
    }

    /**
     * Get the button and scripts contents
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $buttonLabel =  __('Test CrowdSec LAPI connection');
        $this->addData(
            [
                'button_label' => $buttonLabel,
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $this->_urlBuilder->getUrl('crowdsec/system_config_connection/ping'),
            ]
        );

        return $this->_toHtml();
    }
}
