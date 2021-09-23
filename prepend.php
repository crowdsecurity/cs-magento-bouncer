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

/**
 * phpcs:disable PSR1.Files.SideEffects
 * phpcs:disable Magento2.Security.IncludeFile
 * phpcs:disable Magento2.Security.Superglobal.SuperglobalUsageWarning
 * phpcs:disable Magento2.Functions.DiscouragedFunction
 */

use CrowdSec\Bouncer\Helper\Data;
use CrowdSec\Bouncer\Registry\CurrentBouncer;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\App\Response\Http as Response;
use Magento\Framework\App\State;
use Magento\Framework\ObjectManager\ConfigLoaderInterface;

if (PHP_SAPI === 'cli') {
    return;
}
$rootPath = realpath('.');
if (!file_exists($rootPath . '/../app/autoload.php')) {
    return;
}
require_once $rootPath . '/../app/autoload.php';

if (!defined('CROWDSEC_PREPEND_RUNNING_CONTEXT')) {
    define('CROWDSEC_PREPEND_RUNNING_CONTEXT', true);
}

$bootstrap = Bootstrap::create(BP, $_SERVER);
// We need ObjectManager, area and di configurations
$objectManager = $bootstrap->getObjectManager();
$areaList = $objectManager->get(AreaList::class);
$request = $objectManager->get(Request::class);
$state = $objectManager->get(State::class);
$configLoader = $objectManager->get(ConfigLoaderInterface::class);
$helper = $objectManager->get(Data::class);
try {
    // If there is any technical problem while bouncing, don't block the user. Bypass bouncing and log the
    // error.
    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    });
    $areaCode = $areaList->getCodeByFrontName($request->getFrontName());
    $state->setAreaCode($areaCode);
    $objectManager->configure($configLoader->load($areaCode));
    // Check if feature is enabled
    if (!$helper->isEnabled($state->getAreaCode())) {
        return;
    }
    $bouncerRegistry = $objectManager->get(CurrentBouncer::class);
    // Avoid multiple call
    if ($bouncerRegistry->get()) {
        return;
    }
    /** @var \CrowdSec\Bouncer\Model\Bouncer $bouncer */
    $bouncer = $bouncerRegistry->create();
    $bouncer->init();
    $bouncer->run();
    // If ban or captcha remediation wall display is detected
    if ($bouncer->getRemediationDisplay()) {
        $response = $objectManager->get(Response::class);
        $response->sendResponse();
        restore_error_handler();
        // phpcs:ignore Magento2.Security.LanguageConstruct
        exit(0);
    }
    restore_error_handler();
} catch (Exception $e) {
    $helper->critical('', [
        'type' => 'M2_EXCEPTION_WHILE_PREPEND_BOUNCING',
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);

    if ($helper->canDisplayErrors()) {
        throw $e;
    }

    restore_error_handler();
}
