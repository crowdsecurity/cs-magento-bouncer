<?php declare(strict_types=1);
/**
 * Crowdsec_Bouncer Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT LICENSE
 * that is bundled with this package in the file LICENSE
 *
 * @category   Crowdsec
 * @package    Crowdsec_Bouncer
 * @copyright  Copyright (c)  2021+ CrowdSec
 * @author     CrowdSec team
 * @see        https://crowdsec.net CrowdSec Official Website
 * @license    MIT LICENSE
 *
 */

/**
 *
 * @category Crowdsec
 * @package  Crowdsec_Bouncer
 * @module   Bouncer
 * @author   CrowdSec team
 *
 */

namespace Crowdsec\Bouncer\Block\Adminhtml\System\Config\Cache;

use Crowdsec\Bouncer\Constants;
use Crowdsec\Bouncer\Block\Adminhtml\System\Config\Cache;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Prune extends Cache
{
    /** @var string  */
    protected $template = 'Crowdsec_Bouncer::system/config/cache/prune.phtml';

    /**
     * Get the button and scripts contents
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $originalData = $element->getOriginalData();
        $cacheOptions = $this->helper->getCacheSystemOptions();
        $fsCacheLabel = $cacheOptions[Constants::CACHE_SYSTEM_PHPFS] ?? __('Unknown');
        $buttonLabel = $fsCacheLabel ? __('Prune CrowdSec %1 cache', $fsCacheLabel) : $originalData['button_label'];
        $this->addData(
            [
                'button_label' => $buttonLabel,
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $this->_urlBuilder->getUrl('crowdsec/system_config_cache/prune'),
            ]
        );

        return $this->_toHtml();
    }
}
