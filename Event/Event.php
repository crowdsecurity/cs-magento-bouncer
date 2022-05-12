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

namespace CrowdSec\Bouncer\Event;

use Exception;
use CrowdSec\Bouncer\Helper\Event as Helper;
use CrowdSec\Bouncer\Constants;
use Swaggest\JsonSchema\InvalidValue;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\SchemaContract;

class Event
{
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var SchemaContract
     */
    protected $jsonSchema = [];

    /**
     * @var string
     */
    protected $type = 'M2_EVENT';

    /**
     * @var string
     */
    protected $process = '';

    /**
     * Constructor
     *
     * @param Helper $helper
     */
    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Common data for all events
     *
     * @return array
     */
    public function getBaseData(): array
    {
        return [
            'type' => $this->type,
            'ip' => $this->helper->getRemoteIp(),
            'x-forwarded-for-ip' => $this->helper->getForwarderForIp(),
            'bouncer_agent' => Constants::BASE_USER_AGENT,
        ];
    }

    /**
     * Retrieve json schema from some file
     *
     * @param string $path
     * @return SchemaContract
     * @throws InvalidValue
     * @throws \Swaggest\JsonSchema\Exception
     */
    public function getJsonSchema(string $path = __DIR__ . DIRECTORY_SEPARATOR . 'event.json'): SchemaContract
    {
        if (!isset($this->jsonSchema[$path])) {
            $schemaData = json_decode("{\"\$ref\": \"file://$path\"}");
            $this->jsonSchema[$path] = Schema::import($schemaData);
        }

        return $this->jsonSchema[$path];
    }

    /**
     * Check if data event is compliant with json schema
     *
     * @param array $eventData
     * @return bool
     */
    public function validateEvent(array $eventData): bool
    {
        $result = false;
        try {
            $schema = $this->getJsonSchema();
            $schema->in((object)$eventData);
            $result = true;
        } catch (Exception $e) {
            $this->helper->debug('', [
                'type' => 'M2_EXCEPTION_WHILE_VALIDATING_EVENT',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        return $result;
    }
}
