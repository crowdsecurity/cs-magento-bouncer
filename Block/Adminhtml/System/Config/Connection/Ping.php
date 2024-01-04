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
     * Local API Url field Name
     *
     * @var string
     */
    protected $_apiUrlField = 'crowdsec_bouncer_general_connection_api_url';

    /**
     * Local API Auth type field Name
     *
     * @var string
     */
    protected $_apiAuthTypeField = 'crowdsec_bouncer_general_connection_auth_type';

    /**
     * TLS Cert path field Name
     *
     * @var string
     */
    protected $_tlsCertPathField = 'crowdsec_bouncer_general_connection_tls_cert_path';

    /**
     * TLS Cert key field Name
     *
     * @var string
     */
    protected $_tlsKeyPathField = 'crowdsec_bouncer_general_connection_tls_key_path';

    /**
     * TLS verify peer field Name
     *
     * @var string
     */
    protected $_tlsVerifyPeerField = 'crowdsec_bouncer_general_connection_tls_verify_peer';

    /**
     * TLS ca cert path field Name
     *
     * @var string
     */
    protected $_tlsCaCertPathField = 'crowdsec_bouncer_general_connection_tls_ca_cert_path';

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

    /**
     * Api timeout field name
     *
     * @var string
     */
    protected $_apiTimeoutField = 'crowdsec_bouncer_general_connection_api_timeout';

    /**
     * Api connect timeout field name
     *
     * @var string
     */
    protected $_apiConnectTimeoutField = 'crowdsec_bouncer_general_connection_api_connect_timeout';

    /** @var string  */
    protected $template = 'CrowdSec_Bouncer::system/config/connection/ping.phtml';
    /** @var string  */
    protected $oldTemplate = 'CrowdSec_Bouncer::system/config/connection/old/ping.phtml';

    /**
     * Get Local API Url field Name
     *
     * @return string
     */
    public function getUrlField(): string
    {
        return $this->_apiUrlField;
    }

    /**
     * Get auth type field Name
     *
     * @return string
     */
    public function getAuthTypeField(): string
    {
        return $this->_apiAuthTypeField;
    }

    /**
     * Get tls cert path field Name
     *
     * @return string
     */
    public function getTlsCertPathField(): string
    {
        return $this->_tlsCertPathField;
    }

    /**
     * Get tls key path field Name
     *
     * @return string
     */
    public function getTlsKeyPathField(): string
    {
        return $this->_tlsKeyPathField;
    }

    /**
     * Get tls verify peer field Name
     *
     * @return string
     */
    public function getTlsVerifyPeerField(): string
    {
        return $this->_tlsVerifyPeerField;
    }

    /**
     * Get tls ca cert path field Name
     *
     * @return string
     */
    public function getTlsCaCertPathField(): string
    {
        return $this->_tlsCaCertPathField;
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
     * Get api timeout field Name
     *
     * @return string
     */
    public function getApiTimeoutField(): string
    {
        return $this->_apiTimeoutField;
    }

    /**
     * Get api connect timeout field Name
     *
     * @return string
     */
    public function getApiConnectTimeoutField(): string
    {
        return $this->_apiConnectTimeoutField;
    }

    /**
     * Get the button and scripts contents
     *
     * @param AbstractElement $element
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $buttonLabel =  __('Test CrowdSec Local API connection');
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
