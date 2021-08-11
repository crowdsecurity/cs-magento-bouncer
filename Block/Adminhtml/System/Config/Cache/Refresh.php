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

namespace CrowdSec\Bouncer\Block\Adminhtml\System\Config\Cache;

use CrowdSec\Bouncer\Constants;
use CrowdSec\Bouncer\Block\Adminhtml\System\Config\Button;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Refresh extends Button
{
    /** @var string  */
    protected $template = 'CrowdSec_Bouncer::system/config/cache/refresh.phtml';
    /** @var string  */
    protected $oldTemplate = 'CrowdSec_Bouncer::system/config/cache/old/refresh.phtml';

    /**
     * Get the button and scripts contents
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $originalData = $element->getOriginalData();
        $oldCacheSystem = $this->helper->getCacheTechnology();
        $cacheOptions = $this->helper->getCacheSystemOptions();
        $oldCacheLabel = $cacheOptions[$oldCacheSystem] ?? __('Unknown');
        $buttonLabel = $oldCacheLabel ? __('Refresh CrowdSec %1 cache', $oldCacheLabel) : $originalData['button_label'];
        $this->addData(
            [
                'button_label' => $buttonLabel,
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $this->_urlBuilder->getUrl('crowdsec/system_config_cache/refresh'),
            ]
        );

        return $this->_toHtml();
    }
}
